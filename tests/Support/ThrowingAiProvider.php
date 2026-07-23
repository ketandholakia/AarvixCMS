<?php

namespace Tests\Support;

use App\AI\Contracts\AiProvider;
use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;
use App\AI\DTOs\AiUsage;
use App\AI\Enums\AiCapability;
use App\AI\Exceptions\AiProviderException;

class ThrowingAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'throwing';
    }

    public function capabilities(): array
    {
        return [AiCapability::Generate->value];
    }

    public function generate(AiRequestData $request): AiResult
    {
        throw new AiProviderException('Provider is unavailable.');
    }

    public function stream(AiRequestData $request): iterable
    {
        return [];
    }

    public function chat(AiRequestData $request): AiResult
    {
        return AiResult::failure(
            status: \App\AI\Enums\AiStatus::Failed,
            provider: $this->name(),
            model: $request->model ?? 'throwing-model',
            errorClass: AiProviderException::class,
            errorMessage: 'Provider is unavailable.',
            usage: new AiUsage(promptTokens: 0, completionTokens: 0, totalTokens: 0, estimatedCost: '0.000000'),
        );
    }

    public function embedding(AiRequestData $request): AiResult
    {
        return $this->chat($request);
    }

    public function image(AiRequestData $request): AiResult
    {
        return $this->chat($request);
    }

    public function vision(AiRequestData $request): AiResult
    {
        return $this->chat($request);
    }

    public function json(AiRequestData $request): AiResult
    {
        return $this->chat($request);
    }
}
