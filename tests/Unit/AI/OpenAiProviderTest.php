<?php

namespace Tests\Unit\AI;

use App\AI\DTOs\AiRequestData;
use App\AI\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiProviderTest extends TestCase
{
    public function test_generate_maps_chat_completions_response(): void
    {
        config()->set('ai.providers.openai.api_key', 'test-key');
        config()->set('ai.providers.openai.base_url', 'https://api.openai.test/v1');
        config()->set('ai.providers.openai.timeout', 15);
        config()->set('ai.providers.openai.retries', 0);
        config()->set('ai.providers.openai.models.chat', 'test-chat-model');

        Http::fake([
            'https://api.openai.test/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-test',
                'choices' => [
                    [
                        'message' => [
                            'content' => 'OpenAI summary text.',
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 12,
                    'completion_tokens' => 8,
                    'total_tokens' => 20,
                    'estimated_cost' => '0.00042000',
                ],
            ], 200),
        ]);

        $provider = new OpenAiProvider();
        $result = $provider->generate(new AiRequestData([
            'prompt' => 'Summarize this content.',
        ]));

        $this->assertSame('openai', $result->provider);
        $this->assertSame('test-chat-model', $result->model);
        $this->assertSame('OpenAI summary text.', $result->response);
        $this->assertSame('chatcmpl-test', $result->requestId);
        $this->assertSame(20, $result->usage?->totalTokens);

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);

            $this->assertSame('https://api.openai.test/v1/chat/completions', $request->url());
            $this->assertSame('Bearer test-key', $request->header('Authorization')[0] ?? null);
            $this->assertSame('test-chat-model', $payload['model'] ?? null);
            $this->assertSame('Summarize this content.', $payload['messages'][0]['content'] ?? null);

            return true;
        });
    }

    public function test_embedding_maps_embedding_response(): void
    {
        config()->set('ai.providers.openai.api_key', 'test-key');
        config()->set('ai.providers.openai.base_url', 'https://api.openai.test/v1');
        config()->set('ai.providers.openai.retries', 0);
        config()->set('ai.providers.openai.models.embedding', 'test-embedding-model');

        Http::fake([
            'https://api.openai.test/v1/embeddings' => Http::response([
                'id' => 'embeddings-test',
                'data' => [
                    [
                        'embedding' => [0.1, 0.2, 0.3],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 4,
                    'completion_tokens' => 0,
                    'total_tokens' => 4,
                    'estimated_cost' => '0.00008000',
                ],
            ], 200),
        ]);

        $provider = new OpenAiProvider();
        $result = $provider->embedding(new AiRequestData([
            'input' => 'Embed this text.',
        ]));

        $this->assertSame('openai', $result->provider);
        $this->assertSame('test-embedding-model', $result->model);
        $this->assertSame([0.1, 0.2, 0.3], $result->response['vector']);
        $this->assertSame('embeddings-test', $result->requestId);
        $this->assertSame(4, $result->usage?->totalTokens);
    }

    public function test_generate_retries_retryable_gateway_failures(): void
    {
        config()->set('ai.timeout', 20);
        config()->set('ai.retry.attempts', 2);
        config()->set('ai.retry.delay_ms', 0);
        config()->set('ai.providers.openai.api_key', 'test-key');
        config()->set('ai.providers.openai.base_url', 'https://api.openai.test/v1');
        config()->set('ai.providers.openai.timeout', 20);
        config()->set('ai.providers.openai.retries', 2);
        config()->set('ai.providers.openai.models.chat', 'test-chat-model');

        Http::fakeSequence()
            ->push('Temporary upstream failure.', 503)
            ->push([
                'id' => 'chatcmpl-retry',
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Recovered summary text.',
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 8,
                    'completion_tokens' => 4,
                    'total_tokens' => 12,
                    'estimated_cost' => '0.00026000',
                ],
            ], 200);

        $provider = new OpenAiProvider();
        $result = $provider->generate(new AiRequestData([
            'prompt' => 'Retry this summary.',
        ]));

        $this->assertSame('Recovered summary text.', $result->response);
        $this->assertSame('chatcmpl-retry', $result->requestId);

        Http::assertSentCount(2);
    }
}
