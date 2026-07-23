<?php

namespace App\AI\DTOs;

readonly class AiScope
{
    public function __construct(
        public ?int $userId = null,
        public ?string $site = null,
        public ?string $feature = null,
        public array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'site' => $this->site,
            'feature' => $this->feature,
            'metadata' => $this->metadata,
        ];
    }
}
