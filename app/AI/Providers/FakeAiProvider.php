<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;
use App\AI\DTOs\AiUsage;
use App\AI\Enums\AiCapability;
use App\AI\Enums\AiStatus;
use Illuminate\Support\Str;

class FakeAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'fake';
    }

    public function capabilities(): array
    {
        return array_map(
            static fn (AiCapability $capability) => $capability->value,
            AiCapability::cases(),
        );
    }

    public function generate(AiRequestData $request): AiResult
    {
        $operation = $request->input['operation'] ?? 'rewrite';
        $content = trim((string) ($request->input['content'] ?? $request->input['prompt'] ?? ''));
        $tone = trim((string) ($request->input['tone'] ?? ''));
        $title = trim((string) ($request->input['title'] ?? ''));
        $selection = trim((string) ($request->input['selection'] ?? ''));
        $suggestion = $this->buildSuggestion($operation, $content, $tone);
        $mode = $selection !== '' ? 'insert' : 'replace';
        $response = [
            'mode' => $mode,
            'summary' => $this->buildSummary($operation, $tone),
            'plain_text' => $suggestion,
            'suggestion' => $suggestion,
            'operation' => $operation,
            'content_length' => mb_strlen($content),
            'blocks' => $this->buildBlocks($operation, $suggestion, $selection),
        ];

        if ($operation === 'seo') {
            $response['seo'] = $this->buildSeoMetadata($title, $content);
            $response['summary'] = 'SEO metadata preview';
            $response['plain_text'] = $response['seo']['meta_title'] ?? $suggestion;
            $response['suggestion'] = $response['plain_text'];
        } elseif ($operation === 'social') {
            $response['social_variants'] = $this->buildSocialVariants($title, $content);
            $response['summary'] = 'Social post variants preview';
            $response['plain_text'] = $response['social_variants'][0]['text'] ?? $suggestion;
            $response['suggestion'] = $response['plain_text'];
        } elseif ($operation === 'translate') {
            $locales = array_values(array_filter(array_map(
                static fn ($locale): string => strtolower(trim((string) $locale)),
                is_array($request->input['locales'] ?? null) ? $request->input['locales'] : []
            )));
            $translationLocales = $locales !== [] ? $locales : ['hi', 'gu'];
            $response['translations'] = $this->buildTranslationDrafts($title, $content, $translationLocales);
            $response['summary'] = 'Translation drafts preview';
            $response['plain_text'] = $response['translations'][$translationLocales[0]]['title'] ?? $suggestion;
            $response['suggestion'] = $response['plain_text'];
        }

        return $this->buildSuccessResult('generate', $request, $response);
    }

    public function stream(AiRequestData $request): iterable
    {
        yield '[fake-stream:start]';

        if (isset($request->input['prompt']) && is_string($request->input['prompt'])) {
            yield $request->input['prompt'];
        }

        yield '[fake-stream:end]';
    }

    public function chat(AiRequestData $request): AiResult
    {
        return $this->buildSuccessResult('chat', $request);
    }

    public function embedding(AiRequestData $request): AiResult
    {
        return $this->buildSuccessResult('embedding', $request, [
            'vector' => [0.1, 0.2, 0.3, 0.4],
        ]);
    }

    public function image(AiRequestData $request): AiResult
    {
        return $this->buildSuccessResult('image', $request, [
            'data_uri' => 'data:image/gif;base64,R0lGODlhAQABAPAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==',
            'filename' => 'fake-ai-image.gif',
            'alt' => 'Generated AI image',
            'caption' => 'Generated AI image preview',
            'tags' => ['ai', 'generated', 'preview'],
            'ocr_text' => 'Sample OCR text from generated image.',
        ]);
    }

    public function vision(AiRequestData $request): AiResult
    {
        $filename = trim((string) ($request->input['media_filename'] ?? 'media item'));
        $mimeType = trim((string) ($request->input['media_mime_type'] ?? 'image/*'));
        $analysisType = trim((string) ($request->input['analysis_type'] ?? 'vision'));
        $isScreenshot = $analysisType === 'screenshot';
        $summary = $filename !== ''
            ? ($isScreenshot ? "Screenshot analysis for {$filename}." : "Vision analysis for {$filename}.")
            : 'Vision analysis complete.';
        $alt = $filename !== ''
            ? ($isScreenshot ? 'Accessible screenshot description for ' . $filename : 'Accessible description for ' . $filename)
            : 'Accessible description for this media item';
        $caption = ($isScreenshot ? 'Generated screenshot caption for ' : 'Generated vision caption for ') . ($filename !== '' ? $filename : 'the media item');

        return $this->buildSuccessResult('vision', $request, [
            'summary' => $summary,
            'alt' => $alt,
            'caption' => $caption,
            'tags' => array_values(array_filter([
                'vision',
                'analysis',
                $isScreenshot ? 'screenshot' : 'image',
                str_contains($mimeType, '/') ? explode('/', $mimeType, 2)[0] : 'media',
                str_contains($mimeType, '/') ? explode('/', $mimeType, 2)[1] : 'item',
            ])),
            'ocr_text' => ($isScreenshot ? 'Detected UI text from ' : 'Detected text from ') . ($filename !== '' ? $filename : 'the media item') . '.',
            'structured_data' => [
                'filename' => $filename,
                'mime_type' => $mimeType,
                'analysis_type' => $analysisType,
                'width' => $request->input['media_width'] ?? null,
                'height' => $request->input['media_height'] ?? null,
            ],
        ]);
    }

    public function json(AiRequestData $request): AiResult
    {
        return $this->buildSuccessResult('json', $request, [
            'ok' => true,
        ]);
    }

    protected function buildSuccessResult(string $capability, AiRequestData $request, mixed $response = null): AiResult
    {
        $response ??= [
            'capability' => $capability,
            'input' => $request->input,
            'options' => $request->options,
        ];

        return AiResult::success(
            response: $response,
            provider: $this->name(),
            model: $request->model ?? 'fake-model',
            usage: new AiUsage(promptTokens: 1, completionTokens: 1, totalTokens: 2, estimatedCost: '0.000000'),
            latencyMs: 1,
            requestId: (string) Str::uuid(),
            providerRequestId: 'fake-' . Str::random(12),
            metadata: [
                'capability' => $capability,
            ],
        );
    }

    protected function buildSuggestion(string $operation, string $content, string $tone): string
    {
        $prefix = match ($operation) {
            'shorten' => 'Shortened draft: ',
            'expand' => 'Expanded draft: ',
            'summarize' => 'Summary: ',
            'grammar' => 'Grammar pass: ',
            default => 'Rewritten draft: ',
        };

        $toneSuffix = $tone !== '' ? " Tone: {$tone}." : '';

        return $prefix . ($content !== '' ? Str::limit($content, 280) : 'No content provided.') . $toneSuffix;
    }

    protected function buildSummary(string $operation, string $tone): string
    {
        $label = match ($operation) {
            'shorten' => 'Shorten',
            'expand' => 'Expand',
            'summarize' => 'Summarize',
            'grammar' => 'Grammar',
            default => 'Rewrite',
        };

        return $tone !== ''
            ? "{$label} preview in {$tone} tone"
            : "{$label} preview";
    }

    protected function buildBlocks(string $operation, string $suggestion, string $selection): array
    {
        if ($operation === 'summarize') {
            return [[
                'type' => 'list',
                'data' => [
                    'style' => 'unordered',
                    'items' => array_values(array_filter([
                        Str::limit($suggestion, 90),
                        $selection !== '' ? 'Based on selected text.' : 'Based on the whole document.',
                    ])),
                ],
            ]];
        }

        return [[
            'type' => 'paragraph',
            'data' => [
                'text' => $suggestion,
            ],
        ]];
    }

    protected function buildSeoMetadata(string $title, string $content): array
    {
        $source = trim($title !== '' ? $title : $content);
        $source = $source !== '' ? $source : 'Untitled content';

        $metaTitle = Str::limit($source, 58, '');
        $metaDescription = Str::limit(
            preg_replace('/\s+/', ' ', trim($content)) ?: "Learn more about {$source}.",
            155,
            ''
        );
        $slug = Str::slug($source);
        $keywords = $this->buildKeywords($source, $content);

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'slug' => $slug,
            'keywords' => $keywords,
            'og_title' => $metaTitle,
            'og_description' => $metaDescription,
            'twitter_title' => $metaTitle,
            'twitter_description' => $metaDescription,
            'warnings' => $this->buildSeoWarnings($metaTitle, $metaDescription, $keywords),
            'lengths' => [
                'meta_title' => mb_strlen($metaTitle),
                'meta_description' => mb_strlen($metaDescription),
                'keywords' => count($keywords),
            ],
        ];
    }

    protected function buildKeywords(string $title, string $content): array
    {
        $text = Str::of($title . ' ' . $content)->lower()->replaceMatches('/[^a-z0-9\s]+/i', ' ');
        $words = array_values(array_filter(array_unique(preg_split('/\s+/', $text->toString()) ?: []), static fn ($word) => strlen($word) >= 4));
        $words = array_values(array_filter($words, static fn ($word) => ! in_array($word, ['this', 'that', 'with', 'from', 'your', 'have', 'more', 'about', 'learn'], true)));

        return array_slice($words, 0, 5);
    }

    protected function buildSeoWarnings(string $metaTitle, string $metaDescription, array $keywords): array
    {
        $warnings = [];

        if (mb_strlen($metaTitle) < 25) {
            $warnings[] = 'Meta title is short. Aim for 25 to 60 characters.';
        }

        if (mb_strlen($metaTitle) > 60) {
            $warnings[] = 'Meta title is long. Keep it under 60 characters.';
        }

        if (mb_strlen($metaDescription) < 120) {
            $warnings[] = 'Meta description is short. Aim for 120 to 160 characters.';
        }

        if (mb_strlen($metaDescription) > 160) {
            $warnings[] = 'Meta description is long. Keep it under 160 characters.';
        }

        if ($keywords === []) {
            $warnings[] = 'No strong keyword candidates were detected.';
        }

        return $warnings;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function buildSocialVariants(string $title, string $content): array
    {
        $source = trim($title !== '' ? $title : $content);
        $source = $source !== '' ? $source : 'Untitled content';
        $shortSource = Str::limit($source, 70, '');

        return [
            [
                'channel' => 'x',
                'text' => Str::limit("New: {$shortSource} - read the latest update.", 280, ''),
            ],
            [
                'channel' => 'linkedin',
                'text' => Str::limit("We published {$shortSource}. Highlights and takeaways are now available.", 300, ''),
            ],
            [
                'channel' => 'facebook',
                'text' => Str::limit("Fresh on the site: {$shortSource}. Check out the full post.", 280, ''),
            ],
        ];
    }

    /**
     * @param array<int, string> $locales
     * @return array<string, array<string, string>>
     */
    protected function buildTranslationDrafts(string $title, string $content, array $locales): array
    {
        $source = trim($title !== '' ? $title : $content);
        $source = $source !== '' ? $source : 'Untitled content';
        $locales = $locales !== [] ? $locales : ['hi', 'gu'];
        $translations = [];

        foreach ($locales as $locale) {
            $label = strtoupper($locale);
            $translations[$locale] = [
                'title' => "{$source} ({$label} draft)",
                'excerpt' => Str::limit("{$source} translated for {$label}.", 160, ''),
                'body' => "Translated {$label} draft for {$source}.",
                'meta_title' => Str::limit("{$source} {$label}", 255, ''),
                'meta_description' => Str::limit("Translated {$label} version of {$source}.", 255, ''),
            ];
        }

        return $translations;
    }
}
