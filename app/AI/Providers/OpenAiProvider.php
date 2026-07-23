<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;
use App\AI\DTOs\AiUsage;
use App\AI\Enums\AiCapability;
use App\AI\Enums\AiStatus;
use App\AI\Exceptions\AiProviderException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class OpenAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'openai';
    }

    public function capabilities(): array
    {
        return [
            AiCapability::Generate->value,
            AiCapability::Stream->value,
            AiCapability::Chat->value,
            AiCapability::Embedding->value,
            AiCapability::Json->value,
        ];
    }

    public function generate(AiRequestData $request): AiResult
    {
        return $this->chatCompletion($request, false, false);
    }

    public function stream(AiRequestData $request): iterable
    {
        $response = $this->sendChatCompletion($request, true, false);

        yield from $this->streamChunks((string) $response->body());
    }

    public function chat(AiRequestData $request): AiResult
    {
        return $this->chatCompletion($request, false, false);
    }

    public function embedding(AiRequestData $request): AiResult
    {
        $startedAt = microtime(true);
        $payload = [
            'model' => $this->embeddingModel($request),
            'input' => $this->embeddingInput($request),
        ];

        $response = $this->client()->post($this->baseUrl() . '/embeddings', $payload)->throw();
        $body = $this->decodedBody($response->body());
        $vector = data_get($body, 'data.0.embedding', []);

        if (! is_array($vector)) {
            $vector = [];
        }

        return AiResult::success(
            response: [
                'vector' => $vector,
                'raw' => $body,
            ],
            provider: $this->name(),
            model: $payload['model'],
            usage: AiUsage::fromArray(data_get($body, 'usage', [])),
            latencyMs: (int) round((microtime(true) - $startedAt) * 1000),
            requestId: (string) (data_get($body, 'id') ?: Str::uuid()),
            providerRequestId: data_get($body, 'id'),
            metadata: [
                'endpoint' => 'embeddings',
            ],
        );
    }

    public function image(AiRequestData $request): AiResult
    {
        throw new AiProviderException('OpenAI text provider does not support image generation.');
    }

    public function vision(AiRequestData $request): AiResult
    {
        throw new AiProviderException('OpenAI text provider does not support vision requests.');
    }

    public function json(AiRequestData $request): AiResult
    {
        return $this->chatCompletion($request, false, true);
    }

    protected function chatCompletion(AiRequestData $request, bool $stream, bool $jsonMode): AiResult
    {
        $startedAt = microtime(true);
        $response = $this->sendChatCompletion($request, $stream, $jsonMode);
        $body = $this->decodedBody($response->body());
        $content = $this->extractContent($body);

        $parsedJson = null;
        if ($jsonMode && is_string($content)) {
            $candidate = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $parsedJson = $candidate;
            }
        }

        $resultResponse = $parsedJson ?? $content;

        return AiResult::success(
            response: $resultResponse,
            provider: $this->name(),
            model: $this->chatModel($request),
            usage: AiUsage::fromArray(data_get($body, 'usage', [])),
            latencyMs: (int) round((microtime(true) - $startedAt) * 1000),
            requestId: (string) (data_get($body, 'id') ?: Str::uuid()),
            providerRequestId: data_get($body, 'id'),
            metadata: [
                'endpoint' => 'chat/completions',
                'stream' => $stream,
                'json_mode' => $jsonMode,
            ],
        );
    }

    protected function sendChatCompletion(AiRequestData $request, bool $stream, bool $jsonMode)
    {
        $payload = [
            'model' => $this->chatModel($request),
            'messages' => $this->buildMessages($request),
            'stream' => $stream,
        ];

        if (isset($request->options['temperature'])) {
            $payload['temperature'] = (float) $request->options['temperature'];
        }

        if (isset($request->options['max_tokens'])) {
            $payload['max_tokens'] = (int) $request->options['max_tokens'];
        }

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        return $this->client()
            ->post($this->baseUrl() . '/chat/completions', $payload)
            ->throw();
    }

    protected function client(): PendingRequest
    {
        $apiKey = (string) config('ai.providers.openai.api_key', '');

        if ($apiKey === '') {
            throw new AiProviderException('OpenAI provider is missing an API key.');
        }

        $client = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->withToken($apiKey)
            ->timeout((int) config('ai.providers.openai.timeout', 60));

        $organization = (string) config('ai.providers.openai.organization', '');
        if ($organization !== '') {
            $client = $client->withHeaders([
                'OpenAI-Organization' => $organization,
            ]);
        }

        $retries = max(0, (int) config('ai.providers.openai.retries', 0));
        if ($retries > 0) {
            $client = $client->retry($retries, 250);
        }

        return $client;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/');
    }

    protected function chatModel(AiRequestData $request): string
    {
        return $request->model
            ?: (string) data_get(config('ai.providers.openai.models', []), 'chat', 'gpt-4.1-mini');
    }

    protected function embeddingModel(AiRequestData $request): string
    {
        return $request->model
            ?: (string) data_get(config('ai.providers.openai.models', []), 'embedding', 'text-embedding-3-small');
    }

    protected function embeddingInput(AiRequestData $request): string|array
    {
        if (isset($request->input['input'])) {
            return $request->input['input'];
        }

        if (isset($request->input['prompt'])) {
            return (string) $request->input['prompt'];
        }

        if (isset($request->input['content'])) {
            return (string) $request->input['content'];
        }

        return $request->input;
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    protected function buildMessages(AiRequestData $request): array
    {
        $messages = [];

        if (isset($request->input['messages']) && is_array($request->input['messages'])) {
            foreach ($request->input['messages'] as $message) {
                if (! is_array($message)) {
                    continue;
                }

                $role = is_string($message['role'] ?? null) ? strtolower($message['role']) : 'user';
                $content = trim((string) ($message['content'] ?? ''));

                if ($content === '') {
                    continue;
                }

                $messages[] = [
                    'role' => in_array($role, ['system', 'user', 'assistant', 'tool'], true) ? $role : 'user',
                    'content' => $content,
                ];
            }
        }

        if ($messages !== []) {
            return $messages;
        }

        $content = $this->buildPromptFromInput($request);

        return [[
            'role' => 'user',
            'content' => $content,
        ]];
    }

    protected function buildPromptFromInput(AiRequestData $request): string
    {
        $parts = [];

        foreach (['prompt', 'content', 'document', 'selection'] as $key) {
            if (! array_key_exists($key, $request->input)) {
                continue;
            }

            $value = $request->input[$key];

            if (is_array($value) || is_object($value)) {
                $parts[] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            } elseif (is_scalar($value) || $value === null) {
                $parts[] = trim((string) $value);
            }
        }

        $parts = array_values(array_filter($parts, static fn (string $part) => $part !== ''));

        if ($parts === []) {
            return json_encode($request->input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        return implode("\n\n", $parts);
    }

    protected function extractContent(array $body): string
    {
        $content = data_get($body, 'choices.0.message.content');

        if (is_string($content)) {
            return trim($content);
        }

        $content = data_get($body, 'choices.0.text');

        if (is_string($content)) {
            return trim($content);
        }

        return '';
    }

    protected function decodedBody(string $body): array
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw new AiProviderException('OpenAI provider returned an invalid JSON response.');
        }

        return $decoded;
    }

    protected function streamChunks(string $body): iterable
    {
        foreach (preg_split("/\r\n|\n|\r/", $body) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_starts_with($line, 'data:')) {
                continue;
            }

            $payload = trim(substr($line, 5));

            if ($payload === '[DONE]') {
                break;
            }

            $decoded = json_decode($payload, true);
            if (! is_array($decoded)) {
                continue;
            }

            $chunk = data_get($decoded, 'choices.0.delta.content');
            if (is_string($chunk) && $chunk !== '') {
                yield $chunk;
                continue;
            }

            $chunk = data_get($decoded, 'choices.0.text');
            if (is_string($chunk) && $chunk !== '') {
                yield $chunk;
            }
        }
    }
}
