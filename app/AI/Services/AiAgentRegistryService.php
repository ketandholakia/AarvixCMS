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
        $enabled = $this->settings->get("ai.agents.{$key}.enabled", $configEnabled);
        $modelPolicy = $this->resolveModelPolicy($key, $definition);
        $budgets = $this->resolveBudgets($key, $definition);
        $maxSteps = $this->settings->get("ai.agents.{$key}.max_steps", (int) ($definition['max_steps'] ?? 1));
        $maxSeconds = $this->settings->get("ai.agents.{$key}.max_seconds", (int) ($definition['max_seconds'] ?? 60));

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
            modelPolicy: $modelPolicy,
            budgets: $budgets,
            maxSteps: max(1, (int) $maxSteps),
            maxSeconds: max(1, (int) $maxSeconds),
            isEnabled: filter_var($enabled, FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    protected function resolveModelPolicy(string $key, array $definition): array
    {
        $modelPolicy = is_array($definition['model_policy'] ?? null) ? $definition['model_policy'] : [];

        $primary = $this->settings->get(
            "ai.agents.{$key}.primary_model",
            is_string($modelPolicy['primary'] ?? null) ? $modelPolicy['primary'] : null
        );
        $fallback = $this->settings->get(
            "ai.agents.{$key}.fallback_model",
            is_string($modelPolicy['fallback'] ?? null) ? $modelPolicy['fallback'] : null
        );
        $temperature = $this->settings->get(
            "ai.agents.{$key}.temperature",
            $modelPolicy['temperature'] ?? null
        );

        return array_filter([
            'primary' => is_string($primary) && $primary !== '' ? $primary : null,
            'fallback' => is_string($fallback) && $fallback !== '' ? $fallback : null,
            'temperature' => is_numeric($temperature) ? (float) $temperature : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    protected function resolveBudgets(string $key, array $definition): array
    {
        $budgets = is_array($definition['budgets'] ?? null) ? $definition['budgets'] : [];

        $maxTokens = $this->settings->get(
            "ai.agents.{$key}.max_tokens",
            isset($budgets['max_tokens']) ? (int) $budgets['max_tokens'] : null
        );
        $maxCost = $this->settings->get(
            "ai.agents.{$key}.max_cost",
            is_string($budgets['max_cost'] ?? null) ? $budgets['max_cost'] : null
        );

        return array_filter([
            'max_tokens' => is_numeric($maxTokens) ? (int) $maxTokens : null,
            'max_cost' => is_string($maxCost) && $maxCost !== '' ? $maxCost : null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
