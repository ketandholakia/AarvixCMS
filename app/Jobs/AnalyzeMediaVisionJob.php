<?php

namespace App\Jobs;

use App\AI\DTOs\AiRequestData;
use App\AI\Services\AiManager;
use App\Models\AiRequest;
use App\Models\AiMediaAnalysis;
use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use RuntimeException;

class AnalyzeMediaVisionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 240;

    public function __construct(
        public int $mediaId,
        public string $analysisType = 'vision',
        public ?int $userId = null,
        public ?string $provider = null,
        public ?string $model = null,
    ) {
    }

    public function handle(AiManager $aiManager): void
    {
        $media = Media::query()->findOrFail($this->mediaId);

        if (! $media->isImage()) {
            throw new RuntimeException('Only image media can be analyzed.');
        }

        $analysisType = $this->normalizeAnalysisType($this->analysisType);
        $prompt = $analysisType === 'screenshot'
            ? 'Analyze this screenshot for accessibility, OCR, and interface structure.'
            : 'Analyze this media for accessibility, OCR, and structured extraction.';

        $result = $aiManager->vision(new AiRequestData(
            input: [
                'prompt' => $prompt,
                'media_id' => $media->id,
                'analysis_type' => $analysisType,
                'media_filename' => $media->filename,
                'media_url' => $media->url,
                'media_mime_type' => $media->mime_type,
                'media_width' => $media->width,
                'media_height' => $media->height,
                'media_alt_text' => $media->alt_text,
                'media_caption' => $media->caption,
            ],
            options: [
                'user_id' => $this->userId,
                'media_id' => $media->id,
                'queue' => config('ai.queue.low', 'ai-low'),
            ],
            provider: $this->provider,
            model: $this->model,
            promptKey: $analysisType === 'screenshot' ? 'ai.vision.screenshot.v1' : 'ai.vision.media.v1',
            feature: 'vision',
        ));

        if (! is_array($result->response)) {
            throw new RuntimeException('AI vision provider returned an invalid response.');
        }

        $payload = $result->response;
        $aiRequestId = null;

        if (is_string($result->requestId) && $result->requestId !== '') {
            $aiRequestId = AiRequest::query()
                ->where('request_uuid', $result->requestId)
                ->value('id');
        }

        AiMediaAnalysis::updateOrCreate(
            [
                'media_id' => $media->id,
                'analysis_type' => $analysisType,
            ],
            [
                'ai_request_id' => $aiRequestId,
                'provider' => $result->provider,
                'model' => $result->model,
                'summary' => $this->stringValue($payload['summary'] ?? $payload['description'] ?? null),
                'alt_text' => $this->stringValue($payload['alt'] ?? $payload['alt_text'] ?? $media->alt_text),
                'caption' => $this->stringValue($payload['caption'] ?? null),
                'tags' => $this->tags($payload['tags'] ?? []),
                'ocr_text' => $this->stringValue($payload['ocr_text'] ?? null),
                'structured_data' => $this->structuredData($payload),
                'prompt_hash' => hash('sha256', 'Analyze this media for accessibility, OCR, and structured extraction.'),
                'estimated_cost' => $result->usage?->estimatedCost ?? '0.00000000',
                'analyzed_at' => now(),
            ]
        );
    }

    protected function normalizeAnalysisType(string $analysisType): string
    {
        return in_array($analysisType, ['vision', 'screenshot'], true) ? $analysisType : 'vision';
    }

    protected function stringValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(strip_tags($value));

        return $value !== '' ? $value : null;
    }

    protected function tags(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[,|\n]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $tags = [];

        foreach ($value as $tag) {
            if (! is_scalar($tag)) {
                continue;
            }

            $normalized = trim(strip_tags((string) $tag));
            if ($normalized !== '') {
                $tags[] = $normalized;
            }
        }

        return array_values(array_unique($tags));
    }

    protected function structuredData(array $payload): array
    {
        $structured = $payload['structured_data'] ?? $payload;

        return is_array($structured) ? $structured : [];
    }
}
