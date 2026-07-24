<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;
use App\AI\DTOs\AiUsage;
use App\AI\Enums\AiCapability;
use App\AI\Exceptions\AiProviderException;
use App\AI\Services\AiPolicyService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class OllamaProvider implements AiProvider
{
    public function name(): string
    {
        return 'ollama';
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

        $response = $this->client()->post($this->baseUrl() . '/embed', $payload)->throw();
        $body = $this->decodedBody((string) $response->body());
        $vector = data_get($body, 'embeddings.0', []);

        return AiResult::success(
            response: [
                'vector' => is_array($vector) ? $vector : [],
                'raw' => $body,
            ],
            provider: $this->name(),
            model: (string) ($body['model'] ?? $payload['model']),
            usage: $this->usageFromOllama($body, false),
            latencyMs: (int) round((microtime(true) - $startedAt) * 1000),
            requestId: (string) (data_get($body, 'id') ?: Str::uuid()),
            providerRequestId: data_get($body, 'id'),
            metadata: [
                'endpoint' => 'embed',
            ],
        );
    }

    public function image(AiRequestData $request): AiResult
    {
        throw new AiProviderException('Ollama provider does not support image generation in this integration.');
    }

    public function vision(AiRequestData $request): AiResult
    {
        throw new AiProviderException('Ollama provider does not support vision requests in this integration.');
    }

    public function json(AiRequestData $request): AiResult
    {
        return $this->chatCompletion($request, false, true);
    }

    protected function chatCompletion(AiRequestData $request, bool $stream, bool $jsonMode): AiResult
    {
        $startedAt = microtime(true);
        $response = $this->sendChatCompletion($request, $stream, $jsonMode);
        $body = $this->decodedBody((string) $response->body());
        $content = trim((string) data_get($body, 'message.content', ''));

        $parsedJson = null;
        if ($jsonMode && $content !== '') {
            $candidate = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $parsedJson = $candidate;
            }
        }

        return AiResult::success(
            response: $parsedJson ?? $content,
            provider: $this->name(),
            model: (string) ($body['model'] ?? $this->chatModel($request)),
            usage: $this->usageFromOllama($body, true),
            latencyMs: (int) round((microtime(true) - $startedAt) * 1000),
            requestId: (string) (data_get($body, 'id') ?: Str::uuid()),
            providerRequestId: data_get($body, 'id'),
            metadata: [
                'endpoint' => 'chat',
                'stream' => $stream,
                'json_mode' => $jsonMode,
                'done' => (bool) data_get($body, 'done', true),
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

        $options = [];

        if (isset($request->options['temperature'])) {
            $options['temperature'] = (float) $request->options['temperature'];
        }

        if (isset($request->options['max_tokens'])) {
            $options['num_predict'] = (int) $request->options['max_tokens'];
        }

        if ($options !== []) {
            $payload['options'] = $options;
        }

        if ($jsonMode) {
            $payload['format'] = 'json';
        }

        return $this->client()
            ->post($this->baseUrl() . '/chat', $payload)
            ->throw();
    }

    protected function client(): PendingRequest
    {
        $client = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('ai.providers.ollama.timeout', config('ai.timeout', 60)));

        $apiKey = (string) config('ai.providers.ollama.api_key', '');
        if ($apiKey !== '') {
            $client = $client->withToken($apiKey);
        }

        $policy = app(AiPolicyService::class);
        $retries = $policy->retryAttempts($this->name());
        if ($retries > 0) {
            $client = $client->retry($retries, $policy->retryDelayMs(), function (...$arguments) use ($policy) {
                $exception = $arguments[0] ?? null;

                return $exception instanceof Throwable && $policy->isRetryable($exception);
            });
        }

        return $client;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('ai.providers.ollama.base_url', 'http://localhost:11434/api'), '/');
    }

    protected function chatModel(AiRequestData $request): string
    {
        return $request->model
            ?: (string) data_get(config('ai.providers.ollama.models', []), 'chat', 'llama3.2:3b');
    }

    protected function embeddingModel(AiRequestData $request): string
    {
        return $request->model
            ?: (string) data_get(config('ai.providers.ollama.models', []), 'embedding', 'embeddinggemma');
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

        return [[
            'role' => 'user',
            'content' => $this->buildPromptFromInput($request),
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

    protected function decodedBody(string $body): array
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw new AiProviderException('Ollama provider returned an invalid JSON response.');
        }

        return $decoded;
    }

    protected function streamChunks(string $body): iterable
    {
        foreach (preg_split("/\r\n|\n|\r/", $body) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (! is_array($decoded)) {
                continue;
            }

            $chunk = data_get($decoded, 'message.content');
            if (is_string($chunk) && $chunk !== '') {
                yield $chunk;
            }

            if ((bool) data_get($decoded, 'done', false) === true) {
                break;
            }
        }
    }

    protected function usageFromOllama(array $body, bool $includeCompletion): ?AiUsage
    {
        $promptTokens = (int) data_get($body, 'prompt_eval_count', 0);
        $completionTokens = $includeCompletion ? (int) data_get($body, 'eval_count', 0) : 0;
        $totalTokens = $promptTokens + $completionTokens;

        if ($promptTokens === 0 && $completionTokens === 0 && $totalTokens === 0) {
            return null;
        }

        return AiUsage::fromArray([
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'estimated_cost' => '0.00000000',
        ]);
    }
}
