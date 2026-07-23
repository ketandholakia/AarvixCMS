<?php

namespace App\Services;

class AiImageEnrichmentService
{
    public function altText(array $payload, string $fallback): string
    {
        return $this->normalizeText($payload['alt'] ?? $fallback);
    }

    public function caption(array $payload): string
    {
        return $this->normalizeText($payload['caption'] ?? '');
    }

    public function tags(array $payload): array
    {
        $raw = $payload['tags'] ?? [];

        if (is_string($raw)) {
            $raw = preg_split('/[,|\n]+/', $raw) ?: [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $tags = [];

        foreach ($raw as $tag) {
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

    public function ocrText(array $payload): ?string
    {
        $text = $payload['ocr_text'] ?? null;

        if (! is_string($text)) {
            return null;
        }

        $text = trim(strip_tags($text));

        return $text !== '' ? $text : null;
    }

    protected function normalizeText(mixed $value): string
    {
        return is_string($value) ? trim(strip_tags($value)) : '';
    }
}
