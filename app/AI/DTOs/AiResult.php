<?php

namespace App\AI\DTOs;

use App\AI\Enums\AiStatus;

readonly class AiResult
{
    public function __construct(
        public AiStatus $status,
        public mixed $response = null,
        public string $provider = '',
        public string $model = '',
        public ?AiUsage $usage = null,
        public ?int $latencyMs = null,
        public ?string $requestId = null,
        public ?string $providerRequestId = null,
        public ?string $usageRequestId = null,
        public ?string $errorClass = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {
    }

    public static function success(
        mixed $response,
        string $provider,
        string $model,
        ?AiUsage $usage = null,
        ?int $latencyMs = null,
        ?string $requestId = null,
        ?string $providerRequestId = null,
        ?string $usageRequestId = null,
        array $metadata = [],
    ): self {
        return new self(
            status: AiStatus::Succeeded,
            response: $response,
            provider: $provider,
            model: $model,
            usage: $usage,
            latencyMs: $latencyMs,
            requestId: $requestId,
            providerRequestId: $providerRequestId,
            usageRequestId: $usageRequestId,
            metadata: $metadata,
        );
    }

    public static function failure(
        AiStatus $status,
        string $provider,
        string $model,
        string $errorClass,
        string $errorMessage,
        ?int $latencyMs = null,
        ?string $requestId = null,
        ?string $providerRequestId = null,
        ?string $usageRequestId = null,
        array $metadata = [],
    ): self {
        return new self(
            status: $status,
            provider: $provider,
            model: $model,
            latencyMs: $latencyMs,
            requestId: $requestId,
            providerRequestId: $providerRequestId,
            usageRequestId: $usageRequestId,
            errorClass: $errorClass,
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }

    public function withContext(
        string $provider,
        ?string $model = null,
        ?int $latencyMs = null,
        ?string $requestId = null,
        ?string $providerRequestId = null,
        ?string $usageRequestId = null,
    ): self {
        return new self(
            status: $this->status,
            response: $this->response,
            provider: $provider,
            model: $model ?? $this->model,
            usage: $this->usage,
            latencyMs: $latencyMs ?? $this->latencyMs,
            requestId: $requestId ?? $this->requestId,
            providerRequestId: $providerRequestId ?? $this->providerRequestId,
            usageRequestId: $usageRequestId ?? $this->usageRequestId,
            errorClass: $this->errorClass,
            errorMessage: $this->errorMessage,
            metadata: $this->metadata,
        );
    }
}
