<?php

namespace Tests\Unit\AI;

use App\AI\DTOs\AiRequestData;
use App\AI\Providers\OllamaProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaProviderTest extends TestCase
{
    public function test_generate_maps_chat_response(): void
    {
        config()->set('ai.providers.ollama.base_url', 'http://localhost:11434/api');
        config()->set('ai.providers.ollama.timeout', 15);
        config()->set('ai.providers.ollama.models.chat', 'llama3.2:3b');

        Http::fake([
            'http://localhost:11434/api/chat' => Http::response([
                'model' => 'llama3.2:3b',
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Ollama summary text.',
                ],
                'done' => true,
                'prompt_eval_count' => 9,
                'eval_count' => 6,
            ], 200),
        ]);

        $provider = new OllamaProvider();
        $result = $provider->generate(new AiRequestData([
            'prompt' => 'Summarize with Ollama.',
        ]));

        $this->assertSame('ollama', $result->provider);
        $this->assertSame('llama3.2:3b', $result->model);
        $this->assertSame('Ollama summary text.', $result->response);
        $this->assertSame(15, $result->usage?->totalTokens);
    }

    public function test_embedding_maps_embed_response(): void
    {
        config()->set('ai.providers.ollama.base_url', 'http://localhost:11434/api');
        config()->set('ai.providers.ollama.models.embedding', 'embeddinggemma');

        Http::fake([
            'http://localhost:11434/api/embed' => Http::response([
                'model' => 'embeddinggemma',
                'embeddings' => [
                    [0.4, 0.5, 0.6],
                ],
                'prompt_eval_count' => 4,
            ], 200),
        ]);

        $provider = new OllamaProvider();
        $result = $provider->embedding(new AiRequestData([
            'input' => 'Embed with Ollama.',
        ]));

        $this->assertSame('ollama', $result->provider);
        $this->assertSame('embeddinggemma', $result->model);
        $this->assertSame([0.4, 0.5, 0.6], $result->response['vector']);
    }

    public function test_stream_yields_incremental_chat_chunks(): void
    {
        config()->set('ai.providers.ollama.base_url', 'http://localhost:11434/api');
        config()->set('ai.providers.ollama.models.chat', 'llama3.2:3b');

        Http::fake([
            'http://localhost:11434/api/chat' => Http::response(
                "{\"message\":{\"content\":\"Hello \"},\"done\":false}\n{\"message\":{\"content\":\"world\"},\"done\":false}\n{\"message\":{\"content\":\"!\"},\"done\":true}\n",
                200
            ),
        ]);

        $provider = new OllamaProvider();
        $chunks = iterator_to_array($provider->stream(new AiRequestData([
            'prompt' => 'Stream with Ollama.',
        ])), false);

        $this->assertSame(['Hello ', 'world', '!'], $chunks);
    }
}
