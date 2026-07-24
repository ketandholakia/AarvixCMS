<?php

namespace Tests\Feature\Console;

use App\Models\AiRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiReconcileRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_reconcile_requests_command_times_out_stale_pending_and_running_requests(): void
    {
        $stalePending = AiRequest::create([
            'request_uuid' => 'stale-pending',
            'user_id' => null,
            'feature' => 'writer',
            'status' => 'pending',
            'provider' => 'fake',
            'model' => 'fake-writer',
            'prompt_key' => 'writer.rewrite',
            'scope' => [],
            'request_metadata' => [],
            'response_metadata' => [],
            'request_payload' => [],
            'response_payload' => [],
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'estimated_cost' => '0.00000000',
            'latency_ms' => 0,
            'started_at' => null,
            'completed_at' => null,
        ]);

        AiRequest::query()->where('request_uuid', 'stale-pending')->update([
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $staleRunning = AiRequest::create([
            'request_uuid' => 'stale-running',
            'user_id' => null,
            'feature' => 'chat',
            'status' => 'running',
            'provider' => 'fake',
            'model' => 'fake-writer',
            'prompt_key' => 'chat.search',
            'scope' => [],
            'request_metadata' => [],
            'response_metadata' => [],
            'request_payload' => [],
            'response_payload' => [],
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'estimated_cost' => '0.00000000',
            'latency_ms' => 0,
            'started_at' => now()->subHours(1),
            'completed_at' => null,
        ]);

        $freshRunning = AiRequest::create([
            'request_uuid' => 'fresh-running',
            'user_id' => null,
            'feature' => 'chat',
            'status' => 'running',
            'provider' => 'fake',
            'model' => 'fake-writer',
            'prompt_key' => 'chat.search',
            'scope' => [],
            'request_metadata' => [],
            'response_metadata' => [],
            'request_payload' => [],
            'response_payload' => [],
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'estimated_cost' => '0.00000000',
            'latency_ms' => 0,
            'started_at' => now()->subMinutes(10),
            'completed_at' => null,
        ]);

        $this->artisan('ai:reconcile-requests', ['--minutes' => 60])
            ->expectsOutputToContain('Reconciled')
            ->assertExitCode(0);

        $stalePending->refresh();
        $staleRunning->refresh();
        $freshRunning->refresh();

        $this->assertSame('timed_out', $stalePending->status);
        $this->assertSame('timed_out', $staleRunning->status);
        $this->assertSame('running', $freshRunning->status);
        $this->assertSame('TimeoutException', $stalePending->error_class);
        $this->assertSame('TimeoutException', $staleRunning->error_class);
        $this->assertNotNull($stalePending->completed_at);
        $this->assertNotNull($staleRunning->completed_at);
    }
}
