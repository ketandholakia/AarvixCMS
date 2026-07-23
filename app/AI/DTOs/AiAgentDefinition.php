<?php

namespace App\AI\DTOs;

readonly class AiAgentDefinition
{
    /**
     * @param array<int, string> $tools
     * @param array<string, mixed> $memory
     * @param array<int, string> $permissions
     * @param array<string, mixed> $modelPolicy
     * @param array<string, mixed> $budgets
     */
    public function __construct(
        public string $key,
        public int $version,
        public string $name,
        public ?string $description = null,
        public ?string $promptKey = null,
        public array $tools = [],
        public array $memory = [],
        public array $permissions = [],
        public array $modelPolicy = [],
        public array $budgets = [],
        public int $maxSteps = 1,
        public bool $isEnabled = true,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'version' => $this->version,
            'name' => $this->name,
            'description' => $this->description,
            'prompt_key' => $this->promptKey,
            'tools' => $this->tools,
            'memory' => $this->memory,
            'permissions' => $this->permissions,
            'model_policy' => $this->modelPolicy,
            'budgets' => $this->budgets,
            'max_steps' => $this->maxSteps,
            'is_enabled' => $this->isEnabled,
        ];
    }
}
