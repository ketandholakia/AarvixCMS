<?php

namespace Tests\Unit\AI;

use App\AI\Contracts\AiProvider;
use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;
use App\AI\Enums\AiStatus;
use App\AI\Exceptions\AiCapabilityException;
use App\AI\Providers\FakeAiProvider;
use App\AI\Services\AiManager;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class AiManagerTest extends TestCase
{
    public function test_generate_uses_the_configured_provider_and_returns_a_normalized_result(): void
    {
        $manager = $this->makeManager();

        $result = $manager->generate(new AiRequestData(
            input: ['prompt' => 'Write a summary'],
            model: 'fake-summary-model',
        ));

        $this->assertInstanceOf(AiResult::class, $result);
        $this->assertSame(AiStatus::Succeeded, $result->status);
        $this->assertSame('fake', $result->provider);
        $this->assertSame('fake-summary-model', $result->model);
        $this->assertSame('generate', $result->metadata['capability']);
        $this->assertSame('Rewritten draft: Write a summary', $result->response['plain_text']);
    }

    public function test_stream_returns_deterministic_chunks_from_the_fake_provider(): void
    {
        $manager = $this->makeManager();

        $chunks = iterator_to_array($manager->stream(new AiRequestData(
            input: ['prompt' => 'Stream this'],
        )), false);

        $this->assertSame([
            '[fake-stream:start]',
            'Stream this',
            '[fake-stream:end]',
        ], $chunks);
    }

    public function test_unsupported_capability_throws_a_predictable_exception(): void
    {
        $container = new Container();
        $manager = new class($container) extends AiManager {
            public function provider(?string $name = null): AiProvider
            {
                return new class implements AiProvider {
                    public function name(): string
                    {
                        return 'limited';
                    }

                    public function capabilities(): array
                    {
                        return ['generate'];
                    }

                    public function generate(AiRequestData $request): AiResult
                    {
                        return AiResult::success('ok', $this->name(), 'model');
                    }

                    public function stream(AiRequestData $request): iterable
                    {
                        return [];
                    }

                    public function chat(AiRequestData $request): AiResult
                    {
                        return AiResult::success('ok', $this->name(), 'model');
                    }

                    public function embedding(AiRequestData $request): AiResult
                    {
                        return AiResult::success('ok', $this->name(), 'model');
                    }

                    public function image(AiRequestData $request): AiResult
                    {
                        return AiResult::success('ok', $this->name(), 'model');
                    }

                    public function vision(AiRequestData $request): AiResult
                    {
                        return AiResult::success('ok', $this->name(), 'model');
                    }

                    public function json(AiRequestData $request): AiResult
                    {
                        return AiResult::success('ok', $this->name(), 'model');
                    }
                };
            }
        };

        $this->expectException(AiCapabilityException::class);

        $manager->image(new AiRequestData());
    }

    protected function makeManager(): AiManager
    {
        $container = new Container();

        return new AiManager($container, [
            'default_provider' => 'fake',
            'fallback_provider' => 'fake',
            'providers' => [
                'fake' => [
                    'driver' => FakeAiProvider::class,
                    'capabilities' => ['generate', 'stream', 'chat', 'embedding', 'image', 'vision', 'json'],
                ],
            ],
        ]);
    }
}
