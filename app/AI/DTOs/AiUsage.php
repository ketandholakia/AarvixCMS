<?php

namespace App\AI\DTOs;

readonly class AiUsage
{
    public function __construct(
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $totalTokens = 0,
        public string $estimatedCost = '0.000000',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            promptTokens: (int) ($data['promptTokens'] ?? $data['prompt_tokens'] ?? 0),
            completionTokens: (int) ($data['completionTokens'] ?? $data['completion_tokens'] ?? 0),
            totalTokens: (int) ($data['totalTokens'] ?? $data['total_tokens'] ?? 0),
            estimatedCost: (string) ($data['estimatedCost'] ?? $data['estimated_cost'] ?? '0.000000'),
        );
    }

    public function toArray(): array
    {
        return [
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
            'estimated_cost' => $this->estimatedCost,
        ];
    }
}
