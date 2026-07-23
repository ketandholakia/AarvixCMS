<?php

namespace App\AI\Services;

use App\AI\DTOs\AiScope;
use App\AI\DTOs\AiToolDefinition;
use App\AI\Exceptions\AiToolAuthorizationException;
use App\AI\Exceptions\AiToolExecutionException;
use App\Models\Media;
use App\Models\AiTool;
use App\Models\AiToolCall;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiToolRegistryService
{
    public function __construct(
        protected RetrievalService $retrievalService,
        protected ContentEmbeddingSourceResolver $contentEmbeddingSourceResolver,
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

        return $tool->calls()->create([
            'call_uuid' => (string) Str::uuid(),
            'request_uuid' => is_string($context['request_uuid'] ?? null) ? $context['request_uuid'] : null,
            'actor_user_id' => $actor?->id,
            'source_type' => $source?->getMorphClass() ?? ($context['source_type'] ?? null),
            'source_id' => $source?->getKey() ?? ($context['source_id'] ?? null),
            'status' => $tool->confirmation_policy === 'never' ? 'pending' : 'awaiting_approval',
            'approval_state' => $tool->confirmation_policy === 'never' ? 'not_required' : 'pending',
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

        if ($tool->confirmation_policy !== 'never') {
            return [
                'status' => 'approval_required',
                'tool_key' => $tool->key,
                'call_uuid' => $call->call_uuid,
                'call_id' => $call->id,
                'approval_state' => $call->approval_state,
            ];
        }

        try {
            $result = $this->runToolExecutor($tool, $input, $actor, $context, $source);

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

    public function approveCall(AiToolCall $call, ?User $approver = null): array
    {
        $call->loadMissing('tool');
        $tool = $call->tool;

        if ($call->approval_state !== 'pending' || $call->status !== 'awaiting_approval') {
            throw new AiToolExecutionException("AI tool call [{$call->call_uuid}] is not awaiting approval.");
        }

        $this->authorizeApproval($tool, $approver);

        $call->forceFill([
            'approval_state' => 'approved',
            'approved_by_user_id' => $approver?->id,
            'approved_at' => now(),
            'status' => 'pending',
        ])->save();

        $payload = is_array($call->input_payload ?? null) ? $call->input_payload : [];
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        unset($payload['context']);

        $source = $this->resolveSourceModel($call->source_type, $call->source_id);

        try {
            $result = $this->runToolExecutor($tool, $payload, $approver, $context, $source);

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

    public function rejectCall(AiToolCall $call, ?User $approver = null, ?string $reason = null): AiToolCall
    {
        $call->loadMissing('tool');
        $this->authorizeApproval($call->tool, $approver);

        $call->forceFill([
            'approval_state' => 'rejected',
            'status' => 'rejected',
            'approved_by_user_id' => $approver?->id,
            'approved_at' => now(),
            'error_class' => $reason !== null && $reason !== '' ? 'approval_rejected' : null,
            'error_message' => $reason,
            'completed_at' => now(),
        ])->save();

        return $call;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function runToolExecutor(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        return match ($tool->key) {
            'content.search' => $this->executeContentSearch($tool, $input, $actor, $context, $source),
            'content.summary' => $this->executeContentSummary($tool, $input, $actor, $context, $source),
            'media.search' => $this->executeMediaSearch($tool, $input, $actor, $context, $source),
            'seo.propose' => $this->executeSeoProposal($tool, $input, $actor, $context, $source),
            default => throw new AiToolExecutionException("AI tool [{$tool->key}] does not have an executor yet."),
        };
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
        $sourceTypes = $this->normalizeSourceTypes($input['source_types'] ?? null);
        $scope = new AiScope(
            userId: $actor?->id,
            site: is_string($context['site'] ?? null) ? $context['site'] : null,
            feature: 'tool:' . $tool->key,
            metadata: array_merge(
                is_array($context['metadata'] ?? null) ? $context['metadata'] : [],
                ['source_types' => $sourceTypes]
            ),
        );

        return $this->retrievalService->retrieve(
            $scope,
            $query,
            $limit,
            array_filter([
                'source_types' => $sourceTypes,
                'content_type' => is_string($input['content_type'] ?? null) ? $input['content_type'] : null,
            ], static fn ($value): bool => $value !== null && $value !== [])
        );
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeContentSummary(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $source ??= $this->resolveSourceModelFromInput($input);

        if (! $source instanceof Model) {
            throw new AiToolExecutionException('content.summary requires a source model or source identifiers.');
        }

        $summaries = $this->contentEmbeddingSourceResolver->resolve($source)->summaries($source);
        $highlights = array_values(array_filter(array_map(
            static fn (array $summary): string => trim((string) ($summary['content_text'] ?? '')),
            $summaries,
        )));
        $combinedText = trim(implode(' ', $highlights));

        return [
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'title' => trim((string) ($source->title ?? $source->name ?? class_basename($source))),
            'summary' => $this->summarizePlainText($combinedText),
            'highlights' => array_slice($highlights, 0, 3),
            'chunk_count' => count($summaries),
            'visibility' => $summaries[0]['visibility'] ?? null,
            'slug' => (string) ($source->slug ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeSeoProposal(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $source ??= $this->resolveSourceModelFromInput($input);

        $title = trim((string) ($input['title'] ?? $source?->title ?? $source?->name ?? ''));
        $content = trim(implode(' ', array_values(array_filter([
            is_string($input['content'] ?? null) ? $input['content'] : null,
            is_string($source?->excerpt ?? null) ? $source->excerpt : null,
            $source instanceof Model ? $this->combinedSourceText($source) : null,
        ]))));

        return [
            'source_type' => $source?->getMorphClass() ?? ($context['source_type'] ?? null),
            'source_id' => $source?->getKey() ?? ($context['source_id'] ?? null),
            'meta_title' => $this->truncateText($title !== '' ? $title : 'Untitled', 60),
            'meta_description' => $this->truncateText($content !== '' ? $this->summarizePlainText($content) : 'Draft SEO metadata for review.', 155),
            'slug' => is_string($input['slug'] ?? null) ? trim($input['slug']) : null,
            'keywords' => array_values(array_filter(array_map(
                static fn ($keyword): string => trim((string) $keyword),
                (array) ($input['keywords'] ?? [])
            ))),
            'warnings' => [
                'Review before applying this proposal to live content.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeMediaSearch(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $query = trim((string) ($input['query'] ?? ''));
        $limit = max(1, min(25, (int) ($input['limit'] ?? 10)));
        $mimeType = is_string($input['mime_type'] ?? null) ? trim($input['mime_type']) : '';
        $onlyImages = filter_var($input['images_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $queryBuilder = Media::query()->latest('id');

        if ($query !== '') {
            $queryBuilder->where(function ($builder) use ($query): void {
                $builder->where('filename', 'like', '%' . $query . '%')
                    ->orWhere('original_filename', 'like', '%' . $query . '%')
                    ->orWhere('alt_text', 'like', '%' . $query . '%')
                    ->orWhere('caption', 'like', '%' . $query . '%');
            });
        }

        if ($mimeType !== '') {
            $queryBuilder->where('mime_type', 'like', $mimeType . '%');
        }

        if ($onlyImages) {
            $queryBuilder->where('mime_type', 'like', 'image/%');
        }

        $media = $queryBuilder->limit($limit)->get();

        return [
            'query' => $query,
            'count' => $media->count(),
            'items' => $media->map(static function (Media $item): array {
                return [
                    'id' => $item->id,
                    'filename' => $item->filename,
                    'original_filename' => $item->original_filename,
                    'url' => $item->url,
                    'mime_type' => $item->mime_type,
                    'size' => $item->size,
                    'human_size' => $item->human_size,
                    'width' => $item->width,
                    'height' => $item->height,
                    'alt_text' => $item->alt_text,
                    'caption' => $item->caption,
                    'uploaded_by' => $item->uploaded_by,
                    'created_at' => optional($item->created_at)->toISOString(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    protected function summarizeResult(array $result): array
    {
        return [
            'answer' => $result['answer'] ?? null,
            'summary' => $result['summary'] ?? null,
            'meta_title' => $result['meta_title'] ?? null,
            'meta_description' => $result['meta_description'] ?? null,
            'context' => $result['context'] ?? null,
            'count' => isset($result['count']) ? (int) $result['count'] : null,
            'citation_count' => is_array($result['citations'] ?? null) ? count($result['citations']) : 0,
            'chunk_count' => isset($result['chunk_count']) ? (int) $result['chunk_count'] : null,
            'items' => is_array($result['items'] ?? null) ? $this->summarizeMediaItems($result['items']) : [],
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
            'highlights' => is_array($result['highlights'] ?? null) ? array_values($result['highlights']) : [],
            'warnings' => is_array($result['warnings'] ?? null) ? array_values($result['warnings']) : [],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    protected function summarizeMediaItems(array $items): array
    {
        return array_map(static fn (array $item): array => Arr::only($item, [
            'id',
            'filename',
            'original_filename',
            'url',
            'mime_type',
            'human_size',
            'width',
            'height',
            'alt_text',
            'caption',
            'uploaded_by',
            'created_at',
        ]), $items);
    }

    /**
     * @param array<string, mixed> $input
     */
    protected function resolveSourceModelFromInput(array $input): ?Model
    {
        $sourceType = is_string($input['source_type'] ?? null) ? trim($input['source_type']) : '';
        $sourceId = isset($input['source_id']) ? (int) $input['source_id'] : 0;

        return $this->resolveSourceModel($sourceType, $sourceId);
    }

    protected function resolveSourceModel(?string $sourceType, ?int $sourceId): ?Model
    {
        if (! is_string($sourceType) || trim($sourceType) === '' || ! is_int($sourceId) || $sourceId <= 0 || ! class_exists($sourceType)) {
            return null;
        }

        /** @var class-string<Model> $sourceType */
        return $sourceType::query()->find($sourceId);
    }

    protected function authorizeApproval(AiTool $tool, ?User $approver = null): void
    {
        if ($approver === null) {
            throw new AiToolAuthorizationException('Tool approval requires an approver.');
        }

        if ($approver->hasRole('Admin')) {
            return;
        }

        if ($tool->required_permission === null || $tool->required_permission === '') {
            return;
        }

        if (! $approver->hasPermission($tool->required_permission)) {
            throw new AiToolAuthorizationException(
                "You do not have permission to approve AI tool [{$tool->key}]."
            );
        }
    }

    protected function combinedSourceText(Model $source): string
    {
        $summaries = $this->contentEmbeddingSourceResolver->resolve($source)->summaries($source);

        return trim(implode(' ', array_values(array_filter(array_map(
            static fn (array $summary): string => trim((string) ($summary['content_text'] ?? '')),
            $summaries,
        )))));
    }

    protected function summarizePlainText(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?: '');

        if ($text === '') {
            return '';
        }

        if (preg_match('/^(.+?[.!?])\s+/', $text, $matches)) {
            return $this->truncateText($matches[1], 240);
        }

        return $this->truncateText($text, 240);
    }

    protected function truncateText(string $text, int $limit): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?: '');

        if ($text === '' || mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(0, $limit - 1))) . '…';
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
