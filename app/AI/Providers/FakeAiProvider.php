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
        $content = trim((string) ($request->input['content'] ?? ''));
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
            'url' => 'fake://image/generated',
        ]);
    }

    public function vision(AiRequestData $request): AiResult
    {
        return $this->buildSuccessResult('vision', $request);
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
}
