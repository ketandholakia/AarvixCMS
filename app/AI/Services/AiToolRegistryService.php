<?php

namespace App\AI\Services;

use App\AI\DTOs\AiToolDefinition;
use App\AI\Exceptions\AiToolAuthorizationException;
use App\Models\AiTool;
use App\Models\AiToolCall;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiToolRegistryService
{
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
}
