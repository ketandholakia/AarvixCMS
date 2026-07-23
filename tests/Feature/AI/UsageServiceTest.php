<?php

namespace Tests\Feature\AI;

use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;
use App\AI\DTOs\AiUsage;
use App\AI\Enums\AiStatus;
use App\AI\Services\UsageService;
use App\Models\AiRequest;
use App\Models\AiUsageDaily;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_success_creates_request_and_aggregates_daily_usage(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.limits.requests_per_minute', 10);
        config()->set('ai.limits.daily_token_cap', 1000);
        config()->set('ai.limits.daily_cost_cap', '10.00');
        config()->set('ai.limits.monthly_cost_cap', '50.00');
        config()->set('ai.logging.log_prompts', false);
        config()->set('ai.logging.log_responses', false);

        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        /** @var UsageService $service */
        $service = $this->app->make(UsageService::class);

        $requestOne = $service->logStart(
            new AiRequestData(
                input: ['prompt' => 'Write a short intro.'],
                feature: 'writer',
            ),
            'fake',
            'fake-writer'
        );

        $this->assertNotEmpty($requestOne->request_uuid);
        $this->assertNull($requestOne->request_payload);

        $service->logSuccess($requestOne, AiResult::success(
            response: 'First response',
            provider: 'fake',
            model: 'fake-writer',
            usage: new AiUsage(10, 5, 15, '0.00015000'),
            latencyMs: 42,
        ));

        $requestTwo = $service->logStart(
            new AiRequestData(
                input: ['prompt' => 'Write another intro.'],
                feature: 'writer',
            ),
            'fake',
            'fake-writer'
        );

        $service->logSuccess($requestTwo, AiResult::success(
            response: 'Second response',
            provider: 'fake',
            model: 'fake-writer',
            usage: new AiUsage(12, 8, 20, '0.00020000'),
            latencyMs: 55,
        ));

        $this->assertDatabaseCount('ai_requests', 2);
        $this->assertDatabaseCount('ai_usage_daily', 1);

        $latestRequest = AiRequest::query()->latest('id')->first();
        $this->assertNotNull($latestRequest);
        $this->assertSame('succeeded', $latestRequest->status);
        $this->assertSame('fake', $latestRequest->provider);
        $this->assertSame('fake-writer', $latestRequest->model);
        $this->assertSame(12, $latestRequest->prompt_tokens);
        $this->assertSame(8, $latestRequest->completion_tokens);
        $this->assertSame(20, $latestRequest->total_tokens);
        $this->assertSame('0.00020000', (string) $latestRequest->estimated_cost);

        $bucket = AiUsageDaily::query()->first();
        $this->assertNotNull($bucket);
        $this->assertSame(2, $bucket->requests_count);
        $this->assertSame(22, $bucket->prompt_tokens);
        $this->assertSame(13, $bucket->completion_tokens);
        $this->assertSame(35, $bucket->total_tokens);
        $this->assertSame('0.00035000', (string) $bucket->estimated_cost);
    }

    public function test_log_failure_records_sanitized_error_details(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.limits.requests_per_minute', 10);
        config()->set('ai.limits.daily_token_cap', 1000);
        config()->set('ai.limits.daily_cost_cap', '10.00');
        config()->set('ai.limits.monthly_cost_cap', '50.00');

        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        /** @var UsageService $service */
        $service = $this->app->make(UsageService::class);

        $request = $service->logStart(
            new AiRequestData(
                input: ['prompt' => 'Trigger a failure.'],
                feature: 'writer',
            ),
            'fake',
            'fake-writer'
        );

        $record = $service->logFailure(
            $request,
            new \RuntimeException(str_repeat('x', 800)),
            AiStatus::Failed
        );

        $this->assertSame('failed', $record->status);
        $this->assertSame('RuntimeException', $record->error_class);
        $this->assertLessThanOrEqual(503, strlen((string) $record->error_message));
        $this->assertDatabaseCount('ai_requests', 1);
        $this->assertDatabaseCount('ai_usage_daily', 0);
    }
}
