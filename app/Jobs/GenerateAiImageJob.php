<?php

namespace App\Jobs;

use App\AI\DTOs\AiRequestData;
use App\AI\Services\AiManager;
use App\Services\MediaUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateAiImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public string $prompt,
        public string $operation = 'generate',
        public ?int $sourceMediaId = null,
        public ?string $resolution = null,
        public ?int $seed = null,
        public ?int $userId = null,
        public ?string $provider = null,
        public ?string $model = null,
    ) {
    }

    public function handle(AiManager $aiManager, MediaUploadService $mediaUploadService): void
    {
        $result = $aiManager->image(new AiRequestData(
            input: [
                'prompt' => $this->prompt,
                'operation' => $this->operation,
                'source_media_id' => $this->sourceMediaId,
                'resolution' => $this->resolution,
                'seed' => $this->seed,
            ],
            options: [
                'user_id' => $this->userId,
                'source_media_id' => $this->sourceMediaId,
                'resolution' => $this->resolution,
                'seed' => $this->seed,
                'queue' => config('ai.queue.low', 'ai-low'),
            ],
            provider: $this->provider,
            model: $this->model,
            promptKey: 'image.' . $this->operation,
            feature: 'image',
        ));

        if (! is_array($result->response)) {
            throw new RuntimeException('AI image provider returned an invalid response.');
        }

        $payload = $result->response;
        $contents = $this->extractImageContents($payload);
        $originalName = (string) ($payload['filename'] ?? 'ai-image.png');
        $altText = $this->normalizeText($payload['alt'] ?? $this->prompt);
        $caption = $this->normalizeText($payload['caption'] ?? '');

        $media = $mediaUploadService->uploadGeneratedImage(
            $contents,
            $originalName,
            'public',
            'uploads/generated',
            $altText !== '' ? $altText : null,
            $caption !== '' ? $caption : null
        );

        $mediaUploadService->createAiImageAsset($media, [
            'provider' => $result->provider,
            'model' => $result->model,
            'operation' => $this->operation,
            'prompt_hash' => hash('sha256', $this->prompt),
            'resolution' => $this->resolution,
            'seed' => $this->seed,
            'estimated_cost' => $result->usage?->estimatedCost ?? '0.00000000',
            'metadata' => [
                'provider_request_id' => $result->providerRequestId,
                'request_id' => $result->requestId,
                'operation' => $this->operation,
                'prompt' => Str::limit($this->prompt, 500),
            ],
        ]);
    }

    protected function extractImageContents(array $payload): string
    {
        $candidate = (string) ($payload['data_uri'] ?? $payload['url'] ?? $payload['contents'] ?? '');

        if ($candidate === '') {
            throw new RuntimeException('AI image provider returned no image payload.');
        }

        if (str_starts_with($candidate, 'data:')) {
            $parts = explode(',', $candidate, 2);
            if (count($parts) !== 2) {
                throw new RuntimeException('AI image provider returned an invalid data URI.');
            }

            $decoded = base64_decode($parts[1], true);
            if ($decoded === false) {
                throw new RuntimeException('AI image provider returned undecodable image data.');
            }

            return $decoded;
        }

        if (str_starts_with($candidate, 'fake://image/generated')) {
            $decoded = base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==', true);
            if ($decoded === false) {
                throw new RuntimeException('Unable to build fallback AI image bytes.');
            }

            return $decoded;
        }

        if (! preg_match('#^https?://#i', $candidate)) {
            throw new RuntimeException('AI image provider returned an unsupported image reference.');
        }

        $downloaded = @file_get_contents($candidate);
        if ($downloaded === false) {
            throw new RuntimeException('AI image download failed.');
        }

        return $downloaded;
    }

    protected function normalizeText(mixed $value): string
    {
        return is_string($value) ? trim(strip_tags($value)) : '';
    }
}
