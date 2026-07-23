<?php

namespace App\AI\Services;

use App\AI\DTOs\AiAgentDefinition;
use App\AI\DTOs\AiAgentRunResult;
use App\AI\DTOs\AiAgentStep;
use App\AI\Exceptions\AiAgentExecutionException;
use App\AI\Exceptions\AiToolExecutionException;
use App\Models\User;

class AiAgentExecutionService
{
    public function __construct(
        protected AiAgentRegistryService $agents,
        protected AiToolRegistryService $tools,
    ) {
    }

    /**
     * @param array<int, AiAgentStep|array<string, mixed>> $steps
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function execute(string|AiAgentDefinition $agent, array $steps, ?User $actor = null, array $context = []): array
    {
        $definition = $agent instanceof AiAgentDefinition ? $agent : $this->agents->find($agent);

        if (! $definition instanceof AiAgentDefinition) {
            throw new AiAgentExecutionException("AI agent [{$agent}] is not registered.");
        }

        if (! $definition->isEnabled) {
            throw new AiAgentExecutionException("AI agent [{$definition->key}] is disabled.");
        }

        $this->assertAgentPermissions($definition, $actor);

        $normalizedSteps = $this->normalizeSteps($steps);

        if (count($normalizedSteps) > $definition->maxSteps) {
            throw new AiAgentExecutionException(
                "AI agent [{$definition->key}] exceeded its maximum step count of {$definition->maxSteps}."
            );
        }

        $startedAt = microtime(true);
        $plannedTokens = 0;
        $plannedCost = '0.00000000';
        $results = [];

        foreach ($normalizedSteps as $index => $step) {
            $stepNumber = $index + 1;

            $this->assertWithinRuntime($definition, $startedAt);

            if (! in_array($step->toolKey, $definition->tools, true)) {
                throw new AiAgentExecutionException(
                    "AI agent [{$definition->key}] is not allowed to use tool [{$step->toolKey}]."
                );
            }

            $plannedTokens += max(0, $step->estimatedTokens ?? 0);
            $plannedCost = $this->addDecimalStrings($plannedCost, $step->estimatedCost ?? '0.00000000');

            $this->assertBudgets($definition, $plannedTokens, $plannedCost);

            $tool = $this->tools->find($step->toolKey);

            if (! $tool) {
                throw new AiAgentExecutionException("AI tool [{$step->toolKey}] is not registered.");
            }

            if (! $tool->is_enabled) {
                throw new AiAgentExecutionException("AI tool [{$step->toolKey}] is disabled.");
            }

            try {
                $result = $this->tools->execute(
                    $step->toolKey,
                    $step->input,
                    $actor,
                    array_merge($context, [
                        'agent' => $definition->toArray(),
                        'agent_step' => $stepNumber,
                    ])
                );
            } catch (AiToolExecutionException $exception) {
                throw new AiAgentExecutionException(
                    "AI agent [{$definition->key}] failed while executing tool [{$step->toolKey}]: " . $exception->getMessage(),
                    previous: $exception
                );
            }

            $results[] = [
                'step' => $stepNumber,
                'tool_key' => $step->toolKey,
                'status' => $result['status'] ?? 'completed',
                'result' => $result,
            ];

            if (($result['status'] ?? null) === 'approval_required') {
                return (new AiAgentRunResult(
                    status: 'approval_required',
                    agentKey: $definition->key,
                    agentVersion: $definition->version,
                    steps: $results,
                    completedSteps: count($results),
                    estimatedTokens: $plannedTokens,
                    estimatedCost: $this->asDecimalString($plannedCost),
                    halt: [
                        'reason' => 'approval_required',
                        'tool_key' => $step->toolKey,
                        'step' => $stepNumber,
                        'call_uuid' => $result['call_uuid'] ?? null,
                        'call_id' => $result['call_id'] ?? null,
                        'approval_state' => $result['approval_state'] ?? null,
                    ],
                ))->toArray();
            }
        }

        return (new AiAgentRunResult(
            status: 'succeeded',
            agentKey: $definition->key,
            agentVersion: $definition->version,
            steps: $results,
            completedSteps: count($results),
            estimatedTokens: $plannedTokens,
            estimatedCost: $this->asDecimalString($plannedCost),
        ))->toArray();
    }

    /**
     * @param array<int, AiAgentStep|array<string, mixed>> $steps
     * @return array<int, AiAgentStep>
     */
    protected function normalizeSteps(array $steps): array
    {
        return array_values(array_map(static function (AiAgentStep|array $step): AiAgentStep {
            return $step instanceof AiAgentStep ? $step : AiAgentStep::fromArray($step);
        }, $steps));
    }

    protected function assertAgentPermissions(AiAgentDefinition $agent, ?User $actor): void
    {
        if ($agent->permissions === []) {
            return;
        }

        if (! $actor) {
            throw new AiAgentExecutionException("AI agent [{$agent->key}] requires an authenticated actor.");
        }

        foreach ($agent->permissions as $permission) {
            if (! $actor->hasPermission($permission)) {
                throw new AiAgentExecutionException(
                    "You do not have permission to run AI agent [{$agent->key}] without [{$permission}]."
                );
            }
        }
    }

    protected function assertBudgets(AiAgentDefinition $agent, int $plannedTokens, string $plannedCost): void
    {
        $budgetTokens = (int) data_get($agent->budgets, 'max_tokens', 0);
        if ($budgetTokens > 0 && $plannedTokens > $budgetTokens) {
            throw new AiAgentExecutionException(
                "AI agent [{$agent->key}] exceeded its token budget of {$budgetTokens}."
            );
        }

        $budgetCost = (string) data_get($agent->budgets, 'max_cost', '0');
        if ($budgetCost !== '0' && $this->decimalGreaterThan($plannedCost, $budgetCost)) {
            throw new AiAgentExecutionException(
                "AI agent [{$agent->key}] exceeded its cost budget of {$budgetCost}."
            );
        }
    }

    protected function assertWithinRuntime(AiAgentDefinition $agent, float $startedAt): void
    {
        $elapsed = (int) floor(microtime(true) - $startedAt);

        if ($elapsed > $agent->maxSeconds) {
            throw new AiAgentExecutionException(
                "AI agent [{$agent->key}] exceeded its runtime limit of {$agent->maxSeconds} seconds."
            );
        }
    }

    protected function addDecimalStrings(string $left, string $right): string
    {
        return number_format(((float) $left) + ((float) $right), 8, '.', '');
    }

    protected function asDecimalString(string $value): string
    {
        return number_format((float) $value, 8, '.', '');
    }

    protected function decimalGreaterThan(string $left, string $right): bool
    {
        return (float) $left > (float) $right;
    }
}
