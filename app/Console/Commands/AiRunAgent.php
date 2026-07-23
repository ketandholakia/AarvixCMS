<?php

namespace App\Console\Commands;

use App\AI\DTOs\AiAgentStep;
use App\AI\Services\AiAgentExecutionService;
use App\Models\User;
use Illuminate\Console\Command;
use JsonException;
use Throwable;

class AiRunAgent extends Command
{
    protected $signature = 'ai:agent-run {agent : Agent key or configured agent name} {--steps= : JSON array of agent steps} {--context= : Optional JSON context payload} {--actor-id= : Optional user id to execute as}';

    protected $description = 'Execute an AI agent plan and persist the run history.';

    public function handle(AiAgentExecutionService $agents): int
    {
        $agentKey = (string) $this->argument('agent');
        $stepsInput = (string) $this->option('steps');
        $contextInput = (string) $this->option('context');
        $actorId = $this->option('actor-id');

        if (trim($stepsInput) === '') {
            $this->error('You must pass a JSON plan through --steps=');

            return self::FAILURE;
        }

        try {
            $steps = $this->decodeJsonArray($stepsInput);
            $context = trim($contextInput) !== '' ? $this->decodeJsonObject($contextInput) : [];
        } catch (JsonException $exception) {
            $this->error('Invalid JSON: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $actor = null;

        if ($actorId !== null && $actorId !== '') {
            $actor = User::query()->find((int) $actorId);

            if (! $actor instanceof User) {
                $this->error("Actor user [{$actorId}] was not found.");

                return self::FAILURE;
            }
        }

        try {
            $result = $agents->execute($agentKey, $steps, $actor, $context);
        } catch (Throwable $exception) {
            $this->error('Agent execution failed: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function decodeJsonArray(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new JsonException('Expected a JSON array of steps.');
        }

        return array_values(array_map(static function (mixed $step): array {
            if ($step instanceof AiAgentStep) {
                return $step->toArray();
            }

            return is_array($step) ? $step : [];
        }, $decoded));
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonObject(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
