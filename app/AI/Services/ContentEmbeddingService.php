<?php

namespace App\AI\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\BlockParser;

class ContentEmbeddingService
{
    public function __construct(
        protected BlockParser $blockParser,
    ) {
    }

    public function summarize(Model $source, int $chunkIndex = 0): array
    {
        $title = $this->sourceTitle($source);
        $bodyText = $this->extractBodyText($source);
        $extraText = $this->extractAdditionalText($source);
        $contentText = trim(implode("\n\n", array_values(array_filter([$title, $bodyText, $extraText]))));
        $visibility = $this->visibilityFor($source);
        $metadata = $this->metadataFor($source);
        $chunkHash = hash('sha256', implode('|', [
            $source::class,
            (string) $source->getKey(),
            (string) $chunkIndex,
            $contentText,
            $visibility,
        ]));

        return [
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'chunk_index' => $chunkIndex,
            'chunk_hash' => $chunkHash,
            'content_text' => $contentText,
            'metadata' => $metadata,
            'visibility' => $visibility,
            'vector_store' => null,
            'vector_id' => null,
            'embedding_model' => null,
            'chunker_version' => '1',
        ];
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
