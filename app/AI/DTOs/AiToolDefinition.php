<?php

namespace App\AI\DTOs;

readonly class AiToolDefinition
{
    /**
     * @param array<string, mixed> $inputSchema
     * @param array<string, mixed> $outputSchema
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        public string $key,
        public int $version,
        public string $name,
        public ?string $description = null,
        public ?string $category = null,
        public ?string $handler = null,
        public ?string $requiredPermission = null,
        public string $confirmationPolicy = 'never',
        public string $riskClassification = 'read',
        public array $inputSchema = [],
        public array $outputSchema = [],
        public array $configuration = [],
        public int $timeoutSeconds = 30,
        public ?int $rateLimitPerMinute = null,
        public string $auditRedactionPolicy = 'minimal',
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
            'category' => $this->category,
            'handler' => $this->handler,
            'required_permission' => $this->requiredPermission,
            'confirmation_policy' => $this->confirmationPolicy,
            'risk_classification' => $this->riskClassification,
            'input_schema' => $this->inputSchema,
            'output_schema' => $this->outputSchema,
            'configuration' => $this->configuration,
            'timeout_seconds' => $this->timeoutSeconds,
            'rate_limit_per_minute' => $this->rateLimitPerMinute,
            'audit_redaction_policy' => $this->auditRedactionPolicy,
            'is_enabled' => $this->isEnabled,
        ];
    }
}
