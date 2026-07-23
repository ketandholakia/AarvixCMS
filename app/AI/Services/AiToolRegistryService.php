<?php

namespace App\AI\Services;

use App\AI\DTOs\AiToolDefinition;
use App\AI\Exceptions\AiToolAuthorizationException;
use App\AI\Exceptions\AiToolExecutionException;
use App\AI\Services\RetrievalService;
use App\AI\DTOs\AiScope;
use App\Models\AiTool;
use App\Models\AiToolCall;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AiToolRegistryService
{
    public function __construct(
        protected RetrievalService $retrievalService,
    ) {
    }

    /**
     * @param array<int, AiToolDefinition|array<string, mixed>> $definitions
     * @return Collection<int, AiTool>
     */
    public function syncDefinitions(array $definitions): Collection
    {
        return collect($definitions)->map(function (AiToolDefinition|array $definition): AiTool {
            $definition = $definition instanceof AiToolDefinition
                ? $definition
                : new AiToolDefinition(
                    key: (string) ($definition['key'] ?? ''),
                    version: (int) ($definition['version'] ?? 1),
                    name: (string) ($definition['name'] ?? ''),
                    description: $definition['description'] ?? null,
                    category: $definition['category'] ?? null,
                    handler: $definition['handler'] ?? null,
                    requiredPermission: $definition['required_permission'] ?? null,
                    confirmationPolicy: (string) ($definition['confirmation_policy'] ?? 'never'),
                    riskClassification: (string) ($definition['risk_classification'] ?? 'read'),
                    inputSchema: is_array($definition['input_schema'] ?? null) ? $definition['input_schema'] : [],
                    outputSchema: is_array($definition['output_schema'] ?? null) ? $definition['output_schema'] : [],
                    configuration: is_array($definition['configuration'] ?? null) ? $definition['configuration'] : [],
                    timeoutSeconds: (int) ($definition['timeout_seconds'] ?? 30),
                    rateLimitPerMinute: isset($definition['rate_limit_per_minute']) ? (int) $definition['rate_limit_per_minute'] : null,
                    auditRedactionPolicy: (string) ($definition['audit_redaction_policy'] ?? 'minimal'),
                    isEnabled: (bool) ($definition['is_enabled'] ?? true),
                );

            return AiTool::query()->updateOrCreate(
                ['key' => $definition->key],
                array_merge($definition->toArray(), [
                    'tool_uuid' => AiTool::query()->where('key', $definition->key)->value('tool_uuid') ?? (string) Str::uuid(),
                ])
            );
        })->values();
    }

    /**
     * @return Collection<int, AiTool>
     */
    public function all(bool $enabledOnly = true): Collection
    {
        $query = AiTool::query()->orderBy('category')->orderBy('name');

        if ($enabledOnly) {
            $query->where('is_enabled', true);
        }

        return $query->get();
    }

    public function find(string $key): ?AiTool
    {
        return AiTool::query()->where('key', $key)->first();
    }

    public function authorize(AiTool $tool, ?User $actor = null): void
    {
        if (! $tool->is_enabled) {
            throw new AiToolAuthorizationException("AI tool [{$tool->key}] is disabled.");
        }

        if ($tool->required_permission === null || $tool->required_permission === '') {
            return;
        }

        if (! $actor || ! $actor->hasPermission($tool->required_permission)) {
            throw new AiToolAuthorizationException(
                "You do not have permission to use AI tool [{$tool->key}]."
            );
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     */
    public function recordCall(
        AiTool $tool,
        array $input = [],
        ?User $actor = null,
        array $context = [],
        ?Model $source = null,
    ): AiToolCall {
        $this->authorize($tool, $actor);

        $approvalState = $tool->confirmation_policy === 'never' ? 'not_required' : 'pending';

        return $tool->calls()->create([
            'call_uuid' => (string) Str::uuid(),
            'request_uuid' => is_string($context['request_uuid'] ?? null) ? $context['request_uuid'] : null,
            'actor_user_id' => $actor?->id,
            'source_type' => $source?->getMorphClass() ?? ($context['source_type'] ?? null),
            'source_id' => $source?->getKey() ?? ($context['source_id'] ?? null),
            'status' => 'pending',
            'approval_state' => $approvalState,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'input_payload' => array_merge($input, [
                'context' => $context,
            ]),
            'result_summary' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function execute(string $toolKey, array $input = [], ?User $actor = null, array $context = [], ?Model $source = null): array
    {
        $tool = $this->find($toolKey);

        if (! $tool) {
            throw new AiToolExecutionException("AI tool [{$toolKey}] is not registered.");
        }

        $call = $this->recordCall($tool, $input, $actor, $context, $source);

        try {
            $result = match ($tool->key) {
                'content.search' => $this->executeContentSearch($tool, $input, $actor, $context, $source),
                default => throw new AiToolExecutionException("AI tool [{$tool->key}] does not have an executor yet."),
            };

            $call->forceFill([
                'status' => 'succeeded',
                'result_summary' => $this->summarizeResult($result),
                'completed_at' => now(),
            ])->save();

            return $result;
        } catch (\Throwable $throwable) {
            $call->forceFill([
                'status' => 'failed',
                'error_class' => $throwable::class,
                'error_message' => $throwable->getMessage(),
                'completed_at' => now(),
            ])->save();

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeContentSearch(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $query = trim((string) ($input['query'] ?? ''));

        if ($query === '') {
            throw new AiToolExecutionException('content.search requires a non-empty query.');
        }

        $limit = max(1, (int) ($input['limit'] ?? 5));
        $scope = new AiScope(
            userId: $actor?->id,
            site: is_string($context['site'] ?? null) ? $context['site'] : null,
            feature: 'tool:' . $tool->key,
            metadata: array_merge(
                is_array($context['metadata'] ?? null) ? $context['metadata'] : [],
                [
                    'source_types' => $this->normalizeSourceTypes($input['source_types'] ?? null),
                ]
            ),
        );

        return $this->retrievalService->retrieve(
            $scope,
            $query,
            $limit,
            array_filter([
                'source_types' => $this->normalizeSourceTypes($input['source_types'] ?? null),
                'content_type' => is_string($input['content_type'] ?? null) ? $input['content_type'] : null,
            ], static fn ($value): bool => $value !== null && $value !== [])
        );
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    protected function summarizeResult(array $result): array
    {
        return [
            'answer' => $result['answer'] ?? null,
            'context' => $result['context'] ?? null,
            'citation_count' => is_array($result['citations'] ?? null) ? count($result['citations']) : 0,
            'citations' => is_array($result['citations'] ?? null)
                ? array_map(static fn (array $citation): array => Arr::only($citation, [
                    'source_type',
                    'source_id',
                    'title',
                    'chunk_index',
                    'score',
                    'public_url',
                    'accessible_url',
                    'visibility',
                    'content_type',
                ]), $result['citations'])
                : [],
        ];
    }

    /**
     * @param mixed $sourceTypes
     * @return array<int, string>
     */
    protected function normalizeSourceTypes(mixed $sourceTypes): array
    {
        if (is_string($sourceTypes) && $sourceTypes !== '') {
            return [$sourceTypes];
        }

        if (! is_array($sourceTypes)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($sourceType): string => trim((string) $sourceType),
            $sourceTypes,
        )));
    }
}
