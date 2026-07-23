<?php

namespace Tests\Unit\AI;

use App\AI\DTOs\AiRequestData;
use App\AI\Enums\AiCapability;
use App\AI\Providers\FakeAiProvider;
use App\AI\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderContractTest extends TestCase
{
    public function test_fake_provider_generate_contract_is_consistent(): void
    {
        $provider = new FakeAiProvider();
        $result = $provider->generate(new AiRequestData([
            'prompt' => 'Contract check.',
        ], model: 'fake-model'));

        $this->assertSame('fake', $result->provider);
        $this->assertSame('fake-model', $result->model);
        $this->assertSame('succeeded', $result->status->value);
        $this->assertSame('generate', $result->metadata['capability']);
        $this->assertNotEmpty($result->requestId);
        $this->assertArrayHasKey('plain_text', $result->response);
        $this->assertSame('Rewritten draft: Contract check.', $result->response['plain_text']);
    }

    public function test_fake_provider_json_contract_is_consistent(): void
    {
        $provider = new FakeAiProvider();
        $result = $provider->json(new AiRequestData([
            'prompt' => 'Return JSON.',
        ], model: 'fake-json-model'));

        $this->assertSame('fake', $result->provider);
        $this->assertSame('fake-json-model', $result->model);
        $this->assertSame('succeeded', $result->status->value);
        $this->assertSame('json', $result->metadata['capability']);
        $this->assertSame(['ok' => true], $result->response);
    }

    public function test_openai_provider_generate_contract_is_consistent(): void
    {
        config()->set('ai.providers.openai.api_key', 'test-key');
        config()->set('ai.providers.openai.base_url', 'https://api.openai.test/v1');
        config()->set('ai.providers.openai.timeout', 15);
        config()->set('ai.providers.openai.retries', 0);
        config()->set('ai.providers.openai.models.chat', 'test-chat-model');

        Http::fake([
            'https://api.openai.test/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-contract',
                'choices' => [
                    [
                        'message' => [
                            'content' => 'OpenAI contract text.',
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
            'prompt' => 'Contract check.',
        ]));

        $this->assertSame('openai', $result->provider);
        $this->assertSame('test-chat-model', $result->model);
        $this->assertSame('succeeded', $result->status->value);
        $this->assertSame('chatcmpl-contract', $result->requestId);
        $this->assertSame('chat/completions', $result->metadata['endpoint']);
        $this->assertSame('OpenAI contract text.', $result->response);
    }

    public function test_openai_provider_json_contract_is_consistent(): void
    {
        config()->set('ai.providers.openai.api_key', 'test-key');
        config()->set('ai.providers.openai.base_url', 'https://api.openai.test/v1');
        config()->set('ai.providers.openai.timeout', 15);
        config()->set('ai.providers.openai.retries', 0);
        config()->set('ai.providers.openai.models.chat', 'test-json-model');

        Http::fake([
            'https://api.openai.test/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-json-contract',
                'choices' => [
                    [
                        'message' => [
                            'content' => '{"ok":true,"source":"openai"}',
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
        $result = $provider->json(new AiRequestData([
            'prompt' => 'Return JSON.',
        ]));

        $this->assertSame('openai', $result->provider);
        $this->assertSame('test-json-model', $result->model);
        $this->assertSame('succeeded', $result->status->value);
        $this->assertSame('chatcmpl-json-contract', $result->requestId);
        $this->assertSame(['ok' => true, 'source' => 'openai'], $result->response);
    }

    public function test_fake_provider_exposes_required_capabilities(): void
    {
        $provider = new FakeAiProvider();

        $this->assertContains(AiCapability::Generate->value, $provider->capabilities());
        $this->assertContains(AiCapability::Json->value, $provider->capabilities());
        $this->assertContains(AiCapability::Vision->value, $provider->capabilities());
    }
}
