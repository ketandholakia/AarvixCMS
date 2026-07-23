<?php

namespace Tests\Feature\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_queues_a_job_for_matching_webhooks(): void
    {
        Bus::fake();

        $webhook = Webhook::create([
            'name' => 'Post Hook',
            'url' => 'https://example.com/webhook',
            'events' => ['post.updated'],
            'secret' => 'secret',
            'is_active' => true,
        ]);

        app(WebhookService::class)->dispatch('post.updated', ['id' => 1]);

        Bus::assertDispatched(SendWebhookJob::class, function (SendWebhookJob $job) use ($webhook) {
            return $job->webhookId === $webhook->id
                && $job->event === 'post.updated'
                && $job->payload === ['id' => 1];
        });
    }

    public function test_webhook_job_logs_failed_http_responses(): void
    {
        $webhook = Webhook::create([
            'name' => 'Failing Hook',
            'url' => 'https://example.com/webhook',
            'events' => ['post.updated'],
            'secret' => null,
            'is_active' => true,
        ]);

        Http::fake([
            'example.com/webhook' => Http::response('bad gateway', 502),
        ]);

        Log::spy();

        (new SendWebhookJob($webhook->id, 'post.updated', ['id' => 1]))->handle();

        Log::shouldHaveReceived('warning')->once();
    }
}
