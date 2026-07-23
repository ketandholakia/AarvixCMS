<?php

namespace App\AI\Support;

use Illuminate\Support\Str;

class WriterDocument
{
    public static function fromEditorJs(string $document, ?string $selection = null, string $scope = 'document'): array
    {
        $selectedText = self::sanitizeText($selection);

        if ($scope === 'selection' && $selectedText !== '') {
            return [
                'scope' => 'selection',
                'selection' => $selectedText,
                'blocks' => [
                    [
                        'type' => 'selection',
                        'text' => $selectedText,
                    ],
                ],
                'plain_text' => $selectedText,
            ];
        }

        $decoded = json_decode($document, true);

        if (! is_array($decoded)) {
            $plainText = self::sanitizeText($selection ?: $document);

            return [
                'scope' => 'document',
                'selection' => $selectedText !== '' ? $selectedText : null,
                'blocks' => $plainText === '' ? [] : [
                    [
                        'type' => 'raw',
                        'text' => $plainText,
                    ],
                ],
                'plain_text' => $plainText,
            ];
        }

        $blocks = [];

        foreach (($decoded['blocks'] ?? []) as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = is_string($block['type'] ?? null) ? $block['type'] : 'unknown';
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $text = self::extractText($type, $data);

            if ($text === '') {
                continue;
            }

            $blocks[] = [
                'type' => $type,
                'text' => $text,
            ];
        }

        $plainText = trim(implode("\n\n", array_column($blocks, 'text')));

        return [
            'scope' => 'document',
            'selection' => $selectedText !== '' ? $selectedText : null,
            'blocks' => $blocks,
            'plain_text' => $plainText !== '' ? $plainText : self::sanitizeText($document),
        ];
    }

    protected static function extractText(string $type, array $data): string
    {
        if ($type === 'list' && isset($data['items']) && is_array($data['items'])) {
            $items = array_filter(array_map([self::class, 'sanitizeText'], array_map('strval', $data['items'])));

            return trim(implode("\n", $items));
        }

        foreach (['text', 'content', 'caption', 'title'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $text = self::sanitizeText($data[$key]);

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    protected static function sanitizeText(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        return trim(Str::of($text)->stripTags()->replace(["\r\n", "\r"], "\n")->toString());
    }
}
