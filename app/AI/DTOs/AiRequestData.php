<?php

namespace App\AI\DTOs;

readonly class AiRequestData
{
    public function __construct(
        public array $input = [],
        public array $options = [],
        public ?AiScope $scope = null,
        public ?string $provider = null,
        public ?string $model = null,
        public ?string $promptKey = null,
        public ?string $feature = null,
    ) {
    }

    public function withOption(string $key, mixed $value): self
    {
        $options = $this->options;
        $options[$key] = $value;

        return new self(
            input: $this->input,
            options: $options,
            scope: $this->scope,
            provider: $this->provider,
            model: $this->model,
            promptKey: $this->promptKey,
            feature: $this->feature,
        );
    }

    public function withFeature(string $feature): self
    {
        return new self(
            input: $this->input,
            options: $this->options,
            scope: $this->scope,
            provider: $this->provider,
            model: $this->model,
            promptKey: $this->promptKey,
            feature: $feature,
        );
    }
}
