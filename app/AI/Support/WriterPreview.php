<?php

namespace App\AI\Support;

use Illuminate\Support\Str;

class WriterPreview
{
    public static function fromResponse(mixed $response, string $operation, array $document, string $scope = 'document'): array
    {
        $mode = $scope === 'selection' ? 'insert' : 'replace';
        $summary = null;
        $plainText = null;
        $blocks = [];
        $seo = null;

        if (is_array($response)) {
            $mode = self::normalizeMode($response['mode'] ?? null, $mode);
            $summary = self::sanitizeText($response['summary'] ?? null);
            $plainText = self::sanitizeText($response['plain_text'] ?? $response['suggestion'] ?? null);
            $blocks = self::normalizeBlocks($response['blocks'] ?? null);
            $seo = self::normalizeSeo($response['seo'] ?? null);
        } elseif (is_string($response)) {
            $plainText = self::sanitizeText($response);
        }

        if ($plainText === '') {
            $plainText = $document['plain_text'] ?? '';
        }

        if ($summary === '') {
            $summary = match ($operation) {
                'shorten' => 'Shorten preview',
                'expand' => 'Expand preview',
                'summarize' => 'Summary preview',
                'grammar' => 'Grammar preview',
                default => 'Rewrite preview',
            };
        }

        if ($blocks === []) {
            $blocks = self::blocksFromText($plainText);
        }

        return [
            'mode' => $mode,
            'actions' => $mode === 'insert' ? ['insert', 'replace', 'cancel'] : ['replace', 'cancel'],
            'summary' => $summary,
            'plain_text' => $plainText,
            'blocks' => $blocks,
            'seo' => $seo,
        ];
    }

    public static function blocksFromText(string $text): array
    {
        $text = self::sanitizeText($text);

        if ($text === '') {
            return [];
        }

        $paragraphs = preg_split('/\n{2,}/', $text) ?: [$text];
        $blocks = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string) $paragraph);

            if ($paragraph === '') {
                continue;
            }

            $blocks[] = [
                'type' => 'paragraph',
                'data' => [
                    'text' => $paragraph,
                ],
            ];
        }

        return $blocks;
    }

    protected static function normalizeMode(mixed $mode, string $fallback): string
    {
        return in_array($mode, ['replace', 'insert'], true) ? $mode : $fallback;
    }

    protected static function normalizeBlocks(mixed $blocks): array
    {
        if (! is_array($blocks)) {
            return [];
        }

        $normalized = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = is_string($block['type'] ?? null) ? strtolower($block['type']) : 'paragraph';
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            $normalizedBlock = match ($type) {
                'list' => self::normalizeListBlock($data),
                'header' => self::normalizeHeaderBlock($data),
                'image' => self::normalizeImageBlock($data),
                'quote' => self::normalizeQuoteBlock($data),
                'code' => self::normalizeCodeBlock($data),
                'delimiter' => ['type' => 'delimiter', 'data' => new \stdClass()],
                default => self::normalizeParagraphBlock($data),
            };

            if ($normalizedBlock !== null) {
                $normalized[] = $normalizedBlock;
            }
        }

        return $normalized;
    }

    protected static function normalizeParagraphBlock(array $data): array
    {
        return [
            'type' => 'paragraph',
            'data' => [
                'text' => self::sanitizeText($data['text'] ?? $data['content'] ?? $data['caption'] ?? $data['title'] ?? ''),
            ],
        ];
    }

    protected static function normalizeHeaderBlock(array $data): ?array
    {
        $text = self::sanitizeText($data['text'] ?? '');

        if ($text === '') {
            return null;
        }

        $level = max(1, min(6, (int) ($data['level'] ?? 2)));

        return [
            'type' => 'header',
            'data' => [
                'text' => $text,
                'level' => $level,
            ],
        ];
    }

    protected static function normalizeListBlock(array $data): ?array
    {
        $items = $data['items'] ?? [];

        if (! is_array($items)) {
            $items = [];
        }

        $items = array_values(array_filter(array_map(
            static fn ($item) => self::sanitizeText($item),
            $items
        )));

        if ($items === []) {
            return null;
        }

        return [
            'type' => 'list',
            'data' => [
                'style' => in_array($data['style'] ?? 'unordered', ['ordered', 'unordered'], true) ? $data['style'] : 'unordered',
                'items' => $items,
            ],
        ];
    }

    protected static function normalizeQuoteBlock(array $data): ?array
    {
        $text = self::sanitizeText($data['text'] ?? '');

        if ($text === '') {
            return null;
        }

        return [
            'type' => 'quote',
            'data' => [
                'text' => $text,
                'caption' => self::sanitizeText($data['caption'] ?? ''),
            ],
        ];
    }

    protected static function normalizeCodeBlock(array $data): ?array
    {
        $code = self::sanitizeText($data['code'] ?? $data['text'] ?? '');

        if ($code === '') {
            return null;
        }

        return [
            'type' => 'code',
            'data' => [
                'code' => $code,
            ],
        ];
    }

    protected static function normalizeImageBlock(array $data): ?array
    {
        $url = self::sanitizeUrl($data['file']['url'] ?? null);

        if ($url === '') {
            return null;
        }

        return [
            'type' => 'image',
            'data' => [
                'file' => [
                    'url' => $url,
                ],
                'caption' => self::sanitizeText($data['caption'] ?? ''),
                'alt' => self::sanitizeText($data['alt'] ?? $data['caption'] ?? ''),
                'withBorder' => ! empty($data['withBorder']),
                'withBackground' => ! empty($data['withBackground']),
                'stretched' => ! empty($data['stretched']),
            ],
        ];
    }

    protected static function normalizeSeo(mixed $seo): ?array
    {
        if (! is_array($seo)) {
            return null;
        }

        $metaTitle = self::sanitizeText($seo['meta_title'] ?? null);
        $metaDescription = self::sanitizeText($seo['meta_description'] ?? null);
        $slug = self::sanitizeText($seo['slug'] ?? null);

        $keywords = [];
        foreach (($seo['keywords'] ?? []) as $keyword) {
            $keyword = self::sanitizeText($keyword);
            if ($keyword !== '') {
                $keywords[] = $keyword;
            }
        }

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'slug' => $slug,
            'keywords' => $keywords,
            'og_title' => self::sanitizeText($seo['og_title'] ?? $metaTitle),
            'og_description' => self::sanitizeText($seo['og_description'] ?? $metaDescription),
            'twitter_title' => self::sanitizeText($seo['twitter_title'] ?? $metaTitle),
            'twitter_description' => self::sanitizeText($seo['twitter_description'] ?? $metaDescription),
            'warnings' => self::normalizeStringList($seo['warnings'] ?? []),
            'lengths' => [
                'meta_title' => isset($seo['lengths']['meta_title']) ? (int) $seo['lengths']['meta_title'] : mb_strlen($metaTitle),
                'meta_description' => isset($seo['lengths']['meta_description']) ? (int) $seo['lengths']['meta_description'] : mb_strlen($metaDescription),
                'keywords' => isset($seo['lengths']['keywords']) ? (int) $seo['lengths']['keywords'] : count($keywords),
            ],
        ];
    }

    protected static function normalizeStringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($value) => self::sanitizeText($value),
            $values
        )));
    }

    protected static function sanitizeText(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim(Str::of($value)->stripTags()->replace(["\r\n", "\r"], "\n")->toString());
    }

    protected static function sanitizeUrl(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = trim($value);

        if ($value === '' || ! preg_match('#^(https?://|/)#i', $value)) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
