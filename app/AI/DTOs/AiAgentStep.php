<?php

namespace App\AI\DTOs;

readonly class AiAgentStep
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        public string $toolKey,
        public array $input = [],
        public ?int $estimatedTokens = null,
        public ?string $estimatedCost = null,
    ) {
    }

    /**
     * @param array<string, mixed> $step
     */
    public static function fromArray(array $step): self
    {
        return new self(
            toolKey: (string) ($step['tool_key'] ?? ''),
            input: is_array($step['input'] ?? null) ? $step['input'] : [],
            estimatedTokens: isset($step['estimated_tokens']) ? (int) $step['estimated_tokens'] : null,
            estimatedCost: isset($step['estimated_cost']) ? (string) $step['estimated_cost'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tool_key' => $this->toolKey,
            'input' => $this->input,
            'estimated_tokens' => $this->estimatedTokens,
            'estimated_cost' => $this->estimatedCost,
        ];
    }
}
