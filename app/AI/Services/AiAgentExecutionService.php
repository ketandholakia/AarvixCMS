<?php

namespace App\AI\Services;

use App\AI\DTOs\AiAgentDefinition;
use App\AI\DTOs\AiAgentRunResult;
use App\AI\DTOs\AiAgentStep;
use App\AI\Exceptions\AiAgentExecutionException;
use App\AI\Exceptions\AiToolExecutionException;
use App\Models\AiAgentRun;
use App\Models\AiAgentRunStep;
use App\Models\User;
use Illuminate\Support\Str;

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

        $normalizedSteps = $this->normalizeSteps($steps);
        $run = $this->createRunRecord($definition, $actor, $context, $normalizedSteps);

        $startedAt = microtime(true);
        $plannedTokens = 0;
        $plannedCost = '0.00000000';
        $results = [];

        try {
            $this->assertAgentPermissions($definition, $actor);

            if (count($normalizedSteps) > $definition->maxSteps) {
                throw new AiAgentExecutionException(
                    "AI agent [{$definition->key}] exceeded its maximum step count of {$definition->maxSteps}."
                );
            }

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

                $stepRecord = $this->createStepRecord(
                    $run,
                    $stepNumber,
                    $step,
                    max(0, $step->estimatedTokens ?? 0),
                    $step->estimatedCost ?? '0.00000000'
                );

                try {
                    $result = $this->tools->execute(
                        $step->toolKey,
                        $step->input,
                        $actor,
                        array_merge($context, [
                            'agent' => $definition->toArray(),
                            'agent_run_uuid' => $run->run_uuid,
                            'agent_step' => $stepNumber,
                        ])
                    );
                } catch (AiToolExecutionException $exception) {
                    $this->failStepRecord($stepRecord, $exception);
                    $this->failRunRecord($run, $plannedTokens, $plannedCost, $exception);

                    throw new AiAgentExecutionException(
                        "AI agent [{$definition->key}] failed while executing tool [{$step->toolKey}]: " . $exception->getMessage(),
                        previous: $exception
                    );
                }

                $this->completeStepRecord($stepRecord, $result);

                $results[] = [
                    'step' => $stepNumber,
                    'tool_key' => $step->toolKey,
                    'status' => $result['status'] ?? 'completed',
                    'result' => $result,
                ];

                if (($result['status'] ?? null) === 'approval_required') {
                    $output = (new AiAgentRunResult(
                        status: 'approval_required',
                        agentKey: $definition->key,
                        agentVersion: $definition->version,
                        runUuid: $run->run_uuid,
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

                    $this->haltRunRecord($run, $plannedTokens, $plannedCost, $output);

                    return $output;
                }
            }

            $output = (new AiAgentRunResult(
                status: 'succeeded',
                agentKey: $definition->key,
                agentVersion: $definition->version,
                runUuid: $run->run_uuid,
                steps: $results,
                completedSteps: count($results),
                estimatedTokens: $plannedTokens,
                estimatedCost: $this->asDecimalString($plannedCost),
            ))->toArray();

            $this->completeRunRecord($run, $plannedTokens, $plannedCost, $output);

            return $output;
        } catch (\Throwable $throwable) {
            $this->failRunRecord($run, $plannedTokens, $plannedCost, $throwable);

            throw $throwable;
        }
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

    /**
     * @param array<int, AiAgentStep> $steps
     */
    protected function createRunRecord(AiAgentDefinition $agent, ?User $actor, array $context, array $steps): AiAgentRun
    {
        $sourceType = is_string($context['source_type'] ?? null) ? trim((string) $context['source_type']) : null;
        $sourceId = isset($context['source_id']) ? (int) $context['source_id'] : null;

        return AiAgentRun::query()->create([
            'run_uuid' => (string) Str::uuid(),
            'agent_key' => $agent->key,
            'agent_version' => $agent->version,
            'agent_name' => $agent->name,
            'status' => 'running',
            'actor_user_id' => $actor?->id,
            'source_type' => $sourceType !== '' ? $sourceType : null,
            'source_id' => $sourceId && $sourceId > 0 ? $sourceId : null,
            'request_uuid' => is_string($context['request_uuid'] ?? null) ? $context['request_uuid'] : null,
            'prompt_key' => $agent->promptKey,
            'context' => $context ?: null,
            'plan' => array_map(static fn (AiAgentStep $step): array => $step->toArray(), $steps),
            'steps_planned' => count($steps),
            'steps_completed' => 0,
            'estimated_tokens' => 0,
            'estimated_cost' => '0.00000000',
            'started_at' => now(),
        ]);
    }

    protected function createStepRecord(AiAgentRun $run, int $stepNumber, AiAgentStep $step, int $estimatedTokens, string $estimatedCost): AiAgentRunStep
    {
        return $run->steps()->create([
            'step_index' => $stepNumber,
            'tool_key' => $step->toolKey,
            'status' => 'running',
            'input_payload' => $step->toArray(),
            'estimated_tokens' => $estimatedTokens,
            'estimated_cost' => $estimatedCost,
            'started_at' => now(),
        ]);
    }

    protected function completeStepRecord(AiAgentRunStep $stepRecord, array $result): void
    {
        $stepRecord->forceFill([
            'status' => 'approval_required' === ($result['status'] ?? null) ? 'approval_required' : 'succeeded',
            'approval_state' => $result['approval_state'] ?? null,
            'ai_tool_call_id' => isset($result['call_id']) ? (int) $result['call_id'] : null,
            'result_payload' => $result,
            'completed_at' => now(),
        ])->save();
    }

    protected function failStepRecord(AiAgentRunStep $stepRecord, \Throwable $throwable): void
    {
        $stepRecord->forceFill([
            'status' => 'failed',
            'error_class' => $throwable::class,
            'error_message' => $throwable->getMessage(),
            'completed_at' => now(),
        ])->save();
    }

    protected function completeRunRecord(AiAgentRun $run, int $estimatedTokens, string $estimatedCost, array $result): void
    {
        $run->forceFill([
            'status' => 'succeeded',
            'steps_completed' => count($result['steps'] ?? []),
            'estimated_tokens' => $estimatedTokens,
            'estimated_cost' => $this->asDecimalString($estimatedCost),
            'result' => $result,
            'completed_at' => now(),
        ])->save();
    }

    protected function haltRunRecord(AiAgentRun $run, int $estimatedTokens, string $estimatedCost, array $result): void
    {
        $run->forceFill([
            'status' => 'approval_required',
            'steps_completed' => count($result['steps'] ?? []),
            'estimated_tokens' => $estimatedTokens,
            'estimated_cost' => $this->asDecimalString($estimatedCost),
            'result' => $result,
            'halted_at' => now(),
            'completed_at' => now(),
        ])->save();
    }

    protected function failRunRecord(AiAgentRun $run, int $estimatedTokens, string $estimatedCost, \Throwable $throwable): void
    {
        if ($run->exists && $run->status === 'failed') {
            return;
        }

        $run->forceFill([
            'status' => 'failed',
            'steps_completed' => max((int) $run->steps_completed, (int) $run->steps()->count()),
            'estimated_tokens' => $estimatedTokens,
            'estimated_cost' => $this->asDecimalString($estimatedCost),
            'error_class' => $throwable::class,
            'error_message' => $throwable->getMessage(),
            'failed_at' => now(),
            'completed_at' => now(),
        ])->save();
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
