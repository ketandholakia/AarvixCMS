<?php

namespace Tests\Unit\AI;

use App\AI\DTOs\AiRequestData;
use App\AI\Exceptions\AiProviderException;
use App\AI\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiProviderFailureTest extends TestCase
{
    public function test_generate_throws_when_the_provider_returns_invalid_json(): void
    {
        config()->set('ai.providers.openai.api_key', 'test-key');
        config()->set('ai.providers.openai.base_url', 'https://api.openai.test/v1');
        config()->set('ai.providers.openai.timeout', 15);
        config()->set('ai.providers.openai.retries', 0);
        config()->set('ai.providers.openai.models.chat', 'test-chat-model');

        Http::fake([
            'https://api.openai.test/v1/chat/completions' => Http::response('not json at all', 200),
        ]);

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('OpenAI provider returned an invalid JSON response.');

        (new OpenAiProvider())->generate(new AiRequestData([
            'prompt' => 'Break JSON parsing.',
        ]));
    }
}
