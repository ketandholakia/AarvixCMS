<?php

namespace App\AI\Services;

use App\AI\DTOs\AiAgentDefinition;
use App\Services\SettingService;
use Illuminate\Support\Collection;

class AiAgentRegistryService
{
    public function __construct(
        protected SettingService $settings,
    ) {
    }

    /**
     * @return Collection<int, AiAgentDefinition>
     */
    public function all(bool $enabledOnly = false): Collection
    {
        return collect(config('ai.agents', []))
            ->map(function (array $definition, string $key): AiAgentDefinition {
                return $this->makeDefinition($key, $definition);
            })
            ->values()
            ->filter(static function (AiAgentDefinition $definition) use ($enabledOnly): bool {
                return ! $enabledOnly || $definition->isEnabled;
            })
            ->values();
    }

    public function find(string $key): ?AiAgentDefinition
    {
        $definition = config("ai.agents.{$key}");

        if (! is_array($definition)) {
            return null;
        }

        return $this->makeDefinition($key, $definition);
    }

    public function isEnabled(string $key): bool
    {
        $definition = $this->find($key);

        return $definition?->isEnabled ?? false;
    }

    /**
     * @param array<string, mixed> $definition
     */
    protected function makeDefinition(string $key, array $definition): AiAgentDefinition
    {
        $configEnabled = (bool) ($definition['is_enabled'] ?? true);
        $overrideKey = "ai.agents.{$key}.enabled";
        $enabled = $this->settings->get($overrideKey, $configEnabled);

        return new AiAgentDefinition(
            key: $key,
            version: (int) ($definition['version'] ?? 1),
            name: (string) ($definition['name'] ?? ucfirst($key)),
            description: $definition['description'] ?? null,
            promptKey: is_string($definition['prompt'] ?? null) ? $definition['prompt'] : null,
            tools: array_values(array_filter(array_map(
                static fn ($tool): string => trim((string) $tool),
                is_array($definition['tools'] ?? null) ? $definition['tools'] : [],
            ))),
            memory: is_array($definition['memory'] ?? null) ? $definition['memory'] : [],
            permissions: array_values(array_filter(array_map(
                static fn ($permission): string => trim((string) $permission),
                is_array($definition['permissions'] ?? null) ? $definition['permissions'] : [],
            ))),
            modelPolicy: is_array($definition['model_policy'] ?? null) ? $definition['model_policy'] : [],
            budgets: is_array($definition['budgets'] ?? null) ? $definition['budgets'] : [],
            maxSteps: max(1, (int) ($definition['max_steps'] ?? 1)),
            maxSeconds: max(1, (int) ($definition['max_seconds'] ?? 60)),
            isEnabled: filter_var($enabled, FILTER_VALIDATE_BOOLEAN),
        );
    }
}
