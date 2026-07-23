<?php

namespace App\AI\DTOs;

readonly class AiAgentRunResult
{
    /**
     * @param array<int, array<string, mixed>> $steps
     * @param array<string, mixed>|null $halt
     */
    public function __construct(
        public string $status,
        public string $agentKey,
        public int $agentVersion,
        public array $steps = [],
        public int $completedSteps = 0,
        public int $estimatedTokens = 0,
        public string $estimatedCost = '0',
        public ?array $halt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'agent_key' => $this->agentKey,
            'agent_version' => $this->agentVersion,
            'steps' => $this->steps,
            'completed_steps' => $this->completedSteps,
            'estimated_tokens' => $this->estimatedTokens,
            'estimated_cost' => $this->estimatedCost,
            'halt' => $this->halt,
        ];
    }
}
