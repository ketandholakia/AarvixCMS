<?php

namespace Tests\Feature\Console;

use App\AI\Providers\FakeAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_queue_summary_command_prints_configured_queues(): void
    {
        config()->set('ai.queue.high', 'ai-high');
        config()->set('ai.queue.medium', 'ai-medium');
        config()->set('ai.queue.low', 'ai-low');

        $this->artisan('ai:queues')
            ->expectsOutputToContain('ai-high')
            ->expectsOutputToContain('ai-medium')
            ->expectsOutputToContain('ai-low')
            ->assertExitCode(0);
    }

    public function test_ai_health_command_reports_provider_status(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.timeout', 45);
        config()->set('ai.retry.attempts', 3);
        config()->set('ai.retry.delay_ms', 125);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $this->artisan('ai:health')
            ->expectsOutputToContain('AI health check')
            ->expectsOutputToContain('45s')
            ->expectsOutputToContain('3')
            ->expectsOutputToContain('125ms')
            ->expectsOutputToContain('fake')
            ->assertExitCode(0);
    }
}
