<?php

namespace App\Services;

class BlockParser
{
    /**
     * Parse Editor.js JSON output into HTML.
     */
    public function parse(?string $json): string
    {
        if (empty($json)) {
            return '';
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['blocks'])) {
            // It might just be raw HTML from before the transition to Editor.js.
            // Let's just return it as-is if it's not valid Editor.js JSON.
            return $json;
        }

        $html = '';

        foreach ($data['blocks'] as $block) {
            $type = $block['type'] ?? '';
            $data = $block['data'] ?? [];

            switch ($type) {
                case 'paragraph':
                    $html .= '<p>' . ($data['text'] ?? '') . '</p>';
                    break;
                case 'header':
                    $level = $data['level'] ?? 2;
                    $html .= "<h{$level}>" . ($data['text'] ?? '') . "</h{$level}>";
                    break;
                case 'list':
                    $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
                    $items = $data['items'] ?? [];
                    $html .= "<{$style}>";
                    foreach ($items as $item) {
                        $html .= "<li>{$item}</li>";
                    }
                    $html .= "</{$style}>";
                    break;
                case 'image':
                    $url = $data['file']['url'] ?? '';
                    $caption = $data['caption'] ?? '';
                    $withBorder = !empty($data['withBorder']) ? 'border border-gray-200 dark:border-gray-700' : '';
                    $withBackground = !empty($data['withBackground']) ? 'bg-gray-100 p-4' : '';
                    $stretched = !empty($data['stretched']) ? 'w-full' : '';
                    
                    $html .= "<figure class='my-8 {$withBackground}'>";
                    $html .= "<img src='{$url}' alt='{$caption}' class='rounded-xl shadow-sm mx-auto {$withBorder} {$stretched}' />";
                    if ($caption) {
                        $html .= "<figcaption class='text-center text-sm text-gray-500 mt-2'>{$caption}</figcaption>";
                    }
                    $html .= "</figure>";
                    break;
                case 'quote':
                    $text = $data['text'] ?? '';
                    $caption = $data['caption'] ?? '';
                    $html .= "<blockquote class='border-l-4 border-indigo-500 pl-4 py-2 my-6 bg-gray-50 dark:bg-gray-800/50 rounded-r-lg'>";
                    $html .= "<p class='text-lg italic text-gray-700 dark:text-gray-300'>{$text}</p>";
                    if ($caption) {
                        $html .= "<cite class='block mt-2 text-sm text-gray-500'>— {$caption}</cite>";
                    }
                    $html .= "</blockquote>";
                    break;
                case 'delimiter':
                    $html .= '<hr class="my-12 border-gray-200 dark:border-gray-800" />';
                    break;
                case 'code':
                    $code = htmlspecialchars($data['code'] ?? '');
                    $html .= "<pre class='bg-gray-900 text-gray-100 p-4 rounded-xl overflow-x-auto my-6'><code>{$code}</code></pre>";
                    break;
                case 'raw':
                    $html .= $data['html'] ?? '';
                    break;
                default:
                    // Unknown block type
                    break;
            }
        }

        return $html;
    }
}
