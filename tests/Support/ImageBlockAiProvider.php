<?php

namespace Tests\Support;

use App\AI\Contracts\AiProvider;
use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;
use App\AI\DTOs\AiUsage;
use App\AI\Enums\AiCapability;
use App\AI\Enums\AiStatus;
use Illuminate\Support\Str;

class ImageBlockAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'image-block';
    }

    public function capabilities(): array
    {
        return [AiCapability::Generate->value];
    }

    public function generate(AiRequestData $request): AiResult
    {
        return AiResult::success(
            response: [
                'mode' => 'replace',
                'summary' => 'Image-preserving preview',
                'plain_text' => 'Updated article with image.',
                'blocks' => [
                    [
                        'type' => 'paragraph',
                        'data' => ['text' => 'Updated article with image.'],
                    ],
                    [
                        'type' => 'image',
                        'data' => [
                            'file' => ['url' => 'https://cdn.example.test/images/cover.jpg'],
                            'caption' => '<b>Cover image</b>',
                            'alt' => 'Cover image',
                            'withBorder' => true,
                            'withBackground' => false,
                            'stretched' => false,
                        ],
                    ],
                ],
            ],
            provider: $this->name(),
            model: $request->model ?? 'image-block-model',
            usage: new AiUsage(promptTokens: 1, completionTokens: 1, totalTokens: 2, estimatedCost: '0.000000'),
            latencyMs: 1,
            requestId: (string) Str::uuid(),
            providerRequestId: 'image-' . Str::random(12),
        );
    }

    public function stream(AiRequestData $request): iterable
    {
        return [];
    }

    public function chat(AiRequestData $request): AiResult
    {
        return AiResult::failure(
            status: AiStatus::Failed,
            provider: $this->name(),
            model: $request->model ?? 'image-block-model',
            errorClass: 'Unsupported',
            errorMessage: 'Unsupported',
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
