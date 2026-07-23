<?php

namespace App\Jobs;

use App\AI\DTOs\AiRequestData;
use App\AI\Services\AiManager;
use App\Models\Media;
use App\Services\AiImageEnrichmentService;
use App\Services\AiImageCapabilityService;
use App\Services\AiImagePolicyService;
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
        public ?int $replaceMediaId = null,
        public ?string $resolution = null,
        public ?int $seed = null,
        public bool $publicGeneration = false,
        public ?int $userId = null,
        public ?string $provider = null,
        public ?string $model = null,
    ) {
    }

    public function handle(AiManager $aiManager, MediaUploadService $mediaUploadService, AiImageCapabilityService $capabilities, AiImagePolicyService $policy, AiImageEnrichmentService $enrichment): void
    {
        $policy->assertPublicGenerationAllowed($this->publicGeneration);
        $capabilities->assertSupported([
            'operation' => $this->operation,
            'source_media_id' => $this->sourceMediaId,
            'resolution' => $this->resolution,
            'seed' => $this->seed,
            'replace_media_id' => $this->replaceMediaId,
        ], $this->provider ?? config('ai.default_provider', 'fake'));

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
        $altText = $enrichment->altText($payload, $this->prompt);
        $caption = $enrichment->caption($payload);
        $tags = $enrichment->tags($payload);
        $ocrText = $enrichment->ocrText($payload);

        $media = $this->persistMedia($mediaUploadService, $contents, $originalName, $altText, $caption);
        $moderationStatus = $policy->moderationStatus($this->publicGeneration);
        $moderationReviewedAt = $policy->moderationReviewedAt($this->publicGeneration);
        $retentionExpiresAt = $policy->retentionExpiresAt();

        $mediaUploadService->createAiImageAsset($media, [
            'source_media_id' => $this->sourceMediaId ?? $this->replaceMediaId,
            'provider' => $result->provider,
            'model' => $result->model,
            'operation' => $this->operation,
            'prompt_hash' => hash('sha256', $this->prompt),
            'resolution' => $this->resolution,
            'seed' => $this->seed,
            'alt_text' => $altText,
            'caption' => $caption,
            'tags' => $tags,
            'ocr_text' => $ocrText,
            'moderation_status' => $moderationStatus,
            'moderation_reviewed_at' => $moderationReviewedAt,
            'retention_expires_at' => $retentionExpiresAt,
            'estimated_cost' => $result->usage?->estimatedCost ?? '0.00000000',
            'metadata' => [
                'provider_request_id' => $result->providerRequestId,
                'request_id' => $result->requestId,
                'operation' => $this->operation,
                'prompt' => Str::limit($this->prompt, 500),
                'replacement' => $this->replaceMediaId !== null,
                'public_generation' => $this->publicGeneration,
                'moderation_status' => $moderationStatus,
                'retention_expires_at' => $retentionExpiresAt?->toDateTimeString(),
                'tags' => $tags,
                'ocr_text' => $ocrText,
            ],
        ]);
    }

    protected function persistMedia(MediaUploadService $mediaUploadService, string $contents, string $originalName, string $altText, string $caption): Media
    {
        if ($this->replaceMediaId === null) {
            return $mediaUploadService->uploadGeneratedImage(
                $contents,
                $originalName,
                'public',
                'uploads/generated',
                $altText !== '' ? $altText : null,
                $caption !== '' ? $caption : null
            );
        }

        $media = Media::findOrFail($this->replaceMediaId);

        return $mediaUploadService->replaceMediaWithGeneratedImage(
            $media,
            $contents,
            $originalName,
            $altText !== '' ? $altText : null,
            $caption !== '' ? $caption : null
        );
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
}
