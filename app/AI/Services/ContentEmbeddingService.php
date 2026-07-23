<?php

namespace App\AI\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\BlockParser;

class ContentEmbeddingService
{
    protected const DEFAULT_CHUNK_LENGTH = 900;

    public function __construct(
        protected BlockParser $blockParser,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function summaries(Model $source): array
    {
        $chunkerVersion = (string) config('ai.embeddings.chunker_version', '1');
        $embeddingModel = config('ai.embeddings.model');
        $embeddingModel = is_string($embeddingModel) && $embeddingModel !== '' ? $embeddingModel : null;
        $title = $this->sourceTitle($source);
        $bodyText = $this->extractBodyText($source);
        $extraText = $this->extractAdditionalText($source);
        $contentText = trim(implode("\n\n", array_values(array_filter([$title, $bodyText, $extraText]))));
        $visibility = $this->visibilityFor($source);
        $metadata = $this->metadataFor($source);
        $chunks = $this->chunkContentText($contentText, self::DEFAULT_CHUNK_LENGTH);
        $summaries = [];

        foreach ($chunks as $chunkIndex => $chunkText) {
            $summaries[] = $this->buildSummaryForChunk(
                source: $source,
                chunkIndex: $chunkIndex,
                chunkText: $chunkText,
                metadata: $metadata,
                visibility: $visibility,
                chunkerVersion: $chunkerVersion,
                embeddingModel: $embeddingModel,
            );
        }

        return $summaries;
    }

    public function summarize(Model $source, int $chunkIndex = 0): array
    {
        $summaries = $this->summaries($source);

        return $summaries[$chunkIndex] ?? $summaries[0] ?? [];
    }

    protected function sourceTitle(Model $source): string
    {
        return trim((string) ($source->title ?? $source->name ?? class_basename($source)));
    }

    protected function extractBodyText(Model $source): string
    {
        $body = $source->body ?? null;

        if (! is_string($body) || $body === '') {
            return '';
        }

        $html = $this->blockParser->parse($body);
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?: '');

        return $text;
    }

    protected function extractAdditionalText(Model $source): string
    {
        $parts = [];

        if (isset($source->excerpt) && is_string($source->excerpt) && trim($source->excerpt) !== '') {
            $parts[] = trim($source->excerpt);
        }

        if ($source instanceof \App\Models\Entry) {
            $contentType = $source->contentType?->slug ?? null;

            if (is_string($contentType) && $contentType !== '') {
                $parts[] = 'content type: ' . $contentType;
            }

            if (is_array($source->custom_fields ?? null) && $source->custom_fields !== []) {
                $parts[] = 'custom fields: ' . json_encode($this->stringifyCustomFields($source->custom_fields), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        return trim(implode("\n", array_filter($parts)));
    }

    /**
     * @return array<int, string>
     */
    protected function chunkContentText(string $contentText, int $maxLength): array
    {
        $contentText = trim(preg_replace('/\s+/', ' ', $contentText) ?: '');

        if ($contentText === '') {
            return [''];
        }

        if (mb_strlen($contentText) <= $maxLength) {
            return [$contentText];
        }

        $chunks = [];
        $current = '';

        foreach (preg_split('/(\.\s+|\n{2,})/', $contentText, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $candidate = $current === '' ? $part : $current . ' ' . $part;

            if (mb_strlen($candidate) <= $maxLength) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
                $current = '';
            }

            if (mb_strlen($part) <= $maxLength) {
                $current = $part;
                continue;
            }

            foreach ($this->splitLongText($part, $maxLength) as $fragment) {
                $chunks[] = $fragment;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks !== [] ? $chunks : [$contentText];
    }

    /**
     * @return array<int, string>
     */
    protected function splitLongText(string $text, int $maxLength): array
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $chunks = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;

            if (mb_strlen($candidate) <= $maxLength) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
            }

            $current = $word;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks !== [] ? $chunks : [trim($text)];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSummaryForChunk(
        Model $source,
        int $chunkIndex,
        string $chunkText,
        array $metadata,
        string $visibility,
        string $chunkerVersion,
        ?string $embeddingModel,
    ): array {
        $chunkHash = hash('sha256', implode('|', [
            $source::class,
            (string) $source->getKey(),
            (string) $chunkIndex,
            $chunkerVersion,
            (string) ($embeddingModel ?? ''),
            $chunkText,
            $visibility,
        ]));

        return [
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'chunk_index' => $chunkIndex,
            'chunk_hash' => $chunkHash,
            'content_text' => $chunkText,
            'metadata' => $metadata,
            'visibility' => $visibility,
            'vector_store' => null,
            'vector_id' => null,
            'embedding_model' => $embeddingModel,
            'chunker_version' => $chunkerVersion,
        ];
    }

    protected function visibilityFor(Model $source): string
    {
        if (method_exists($source, 'trashed') && $source->trashed()) {
            return 'deleted';
        }

        if ((string) ($source->status ?? '') !== 'published') {
            return 'private';
        }

        if (isset($source->is_premium) && (bool) $source->is_premium) {
            return 'restricted';
        }

        return 'public';
    }

    protected function metadataFor(Model $source): array
    {
        $metadata = [
            'title' => $this->sourceTitle($source),
            'slug' => (string) ($source->slug ?? ''),
            'status' => (string) ($source->status ?? ''),
            'published_at' => optional($source->published_at ?? null)->toISOString(),
        ];

        if (isset($source->is_premium)) {
            $metadata['is_premium'] = (bool) $source->is_premium;
        }

        if ($source instanceof \App\Models\Entry) {
            $metadata['content_type'] = $source->contentType?->slug;
            $metadata['template'] = $source->template ?? null;
        } elseif ($source instanceof \App\Models\Page) {
            $metadata['template'] = $source->template ?? null;
        }

        return Arr::whereNotNull($metadata);
    }

    protected function stringifyCustomFields(array $fields): array
    {
        $result = [];

        foreach ($fields as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $stringValue = trim((string) $value);
                if ($stringValue !== '') {
                    $result[$key] = $stringValue;
                }

                continue;
            }

            $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json !== false && $json !== '') {
                $result[$key] = $json;
            }
        }

        return $result;
    }
}
