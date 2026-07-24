<?php

namespace Tests\Unit\AI;

use App\AI\DTOs\AiRequestData;
use App\AI\Providers\GeminiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiProviderTest extends TestCase
{
    public function test_generate_maps_openai_compatible_chat_response(): void
    {
        config()->set('ai.providers.gemini.api_key', 'gemini-key');
        config()->set('ai.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
        config()->set('ai.providers.gemini.timeout', 15);
        config()->set('ai.providers.gemini.models.chat', 'gemini-2.5-flash');

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions' => Http::response([
                'id' => 'gemini-chat-1',
                'choices' => [[
                    'message' => [
                        'content' => 'Gemini summary text.',
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 11,
                    'completion_tokens' => 7,
                    'total_tokens' => 18,
                ],
            ], 200),
        ]);

        $provider = new GeminiProvider();
        $result = $provider->generate(new AiRequestData([
            'prompt' => 'Summarize for Gemini.',
        ]));

        $this->assertSame('gemini', $result->provider);
        $this->assertSame('gemini-2.5-flash', $result->model);
        $this->assertSame('Gemini summary text.', $result->response);
        $this->assertSame('gemini-chat-1', $result->requestId);

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);

            $this->assertSame('https://generativelanguage.googleapis.com/v1beta/openai/chat/completions', $request->url());
            $this->assertSame('Bearer gemini-key', $request->header('Authorization')[0] ?? null);
            $this->assertSame('gemini-2.5-flash', $payload['model'] ?? null);

            return true;
        });
    }

    public function test_embedding_maps_openai_compatible_embedding_response(): void
    {
        config()->set('ai.providers.gemini.api_key', 'gemini-key');
        config()->set('ai.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
        config()->set('ai.providers.gemini.models.embedding', 'gemini-embedding-001');

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/openai/embeddings' => Http::response([
                'id' => 'gemini-embed-1',
                'data' => [[
                    'embedding' => [0.9, 0.8, 0.7],
                ]],
                'usage' => [
                    'prompt_tokens' => 3,
                    'completion_tokens' => 0,
                    'total_tokens' => 3,
                ],
            ], 200),
        ]);

        $provider = new GeminiProvider();
        $result = $provider->embedding(new AiRequestData([
            'input' => 'Embed for Gemini.',
        ]));

        $this->assertSame('gemini', $result->provider);
        $this->assertSame('gemini-embedding-001', $result->model);
        $this->assertSame([0.9, 0.8, 0.7], $result->response['vector']);
    }
}
