<?php

namespace Tests\Feature\AI;

use App\AI\DTOs\AiRequestData;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use App\AI\Providers\FakeAiProvider;
use App\AI\Services\AiManager;
use App\AI\Services\AiPolicyService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Tests\TestCase;

class AiPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_kill_switch_blocks_generate_and_stream_calls(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        app(SettingService::class)->set('ai.enabled', false, 'ai', 'boolean');

        /** @var AiManager $manager */
        $manager = $this->app->make(AiManager::class);

        $this->expectException(AiTimeoutException::class);

        $manager->generate(new AiRequestData(
            input: ['prompt' => 'Blocked by kill switch'],
            feature: 'writer',
        ));
    }

    public function test_feature_level_toggle_blocks_writer_requests(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        app(SettingService::class)->set('ai.writer.enabled', false, 'ai', 'boolean');

        /** @var AiManager $manager */
        $manager = $this->app->make(AiManager::class);

        $this->expectException(AiTimeoutException::class);

        $manager->generate(new AiRequestData(
            input: ['prompt' => 'Blocked writer feature'],
            feature: 'writer',
        ));
    }

    public function test_policy_service_classifies_retryable_failures(): void
    {
        /** @var AiPolicyService $policy */
        $policy = $this->app->make(AiPolicyService::class);

        $this->assertTrue($policy->isRetryable(new AiRateLimitException('Too many requests.')));
        $this->assertTrue($policy->isRetryable(new AiTimeoutException('Timed out.')));
        $this->assertTrue($policy->isRetryable(new ConnectionException('Connection failed.')));
        $this->assertTrue($policy->isRetryable(new \RuntimeException('Server error', 503)));
        $this->assertFalse($policy->isRetryable(new \RuntimeException('Validation error', 422)));
    }
}
