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

        return $this->buildSuccessResult('generate', $request, [
            'suggestion' => $this->buildSuggestion($operation, $content, $tone),
            'operation' => $operation,
            'content_length' => mb_strlen($content),
        ]);
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
}
