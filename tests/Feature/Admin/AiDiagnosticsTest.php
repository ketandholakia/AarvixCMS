<?php

namespace Tests\Feature\Admin;

use App\AI\Providers\FakeAiProvider;
use App\Models\AiAgentRun;
use App\Models\AiRequest;
use App\Models\AiTool;
use App\Models\AiToolCall;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUpAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'AiToolSeeder']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->first());

        return $admin;
    }

    public function test_admin_can_view_ai_diagnostics(): void
    {
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);
        config()->set('ai.agents.seo', [
            'version' => 1,
            'name' => 'SEO Agent',
            'description' => 'Produces metadata proposals.',
            'prompt' => 'ai.agents.seo.v1',
            'tools' => ['content.summary', 'seo.propose'],
            'permissions' => ['use_ai_writer'],
            'budgets' => ['max_tokens' => 1800, 'max_cost' => '0.50'],
            'max_steps' => 3,
            'is_enabled' => true,
        ]);

        $admin = $this->setUpAdmin();

        AiRequest::create([
            'request_uuid' => 'diag-req-1',
            'user_id' => $admin->id,
            'feature' => 'writer',
            'status' => 'succeeded',
            'provider' => 'fake',
            'model' => 'fake-writer',
            'prompt_key' => 'writer.rewrite',
            'scope' => [],
            'request_metadata' => [],
            'response_metadata' => [],
            'request_payload' => [],
            'response_payload' => [],
            'prompt_tokens' => 11,
            'completion_tokens' => 7,
            'total_tokens' => 18,
            'estimated_cost' => '0.00018000',
            'latency_ms' => 145,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2),
        ]);

        AiRequest::create([
            'request_uuid' => 'diag-req-2',
            'user_id' => $admin->id,
            'feature' => 'writer',
            'status' => 'failed',
            'provider' => 'fake',
            'model' => 'fake-writer',
            'prompt_key' => 'writer.rewrite',
            'scope' => [],
            'request_metadata' => [],
            'response_metadata' => [],
            'request_payload' => [],
            'response_payload' => [],
            'prompt_tokens' => 6,
            'completion_tokens' => 0,
            'total_tokens' => 6,
            'estimated_cost' => '0.00006000',
            'latency_ms' => 95,
            'error_class' => 'RuntimeException',
            'error_message' => 'Provider unavailable',
            'started_at' => now()->subHours(1),
            'completed_at' => now()->subHours(1),
        ]);

        $tool = AiTool::query()->where('key', 'content.summary')->firstOrFail();

        AiToolCall::create([
            'tool_id' => $tool->id,
            'call_uuid' => 'diag-call-1',
            'request_uuid' => 'diag-req-1',
            'actor_user_id' => $admin->id,
            'source_type' => 'post',
            'source_id' => 1,
            'status' => 'succeeded',
            'approval_state' => 'not_required',
            'input_payload' => ['source_type' => 'post', 'source_id' => 1],
            'result_summary' => ['summary' => 'Example summary'],
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2),
        ]);

        AiToolCall::create([
            'tool_id' => $tool->id,
            'call_uuid' => 'diag-call-2',
            'request_uuid' => 'diag-req-2',
            'actor_user_id' => $admin->id,
            'source_type' => 'post',
            'source_id' => 1,
            'status' => 'failed',
            'approval_state' => 'rejected',
            'input_payload' => ['source_type' => 'post', 'source_id' => 1],
            'result_summary' => ['summary' => null],
            'error_class' => 'RuntimeException',
            'error_message' => 'Tool execution failed',
            'started_at' => now()->subHours(1),
            'completed_at' => now()->subHours(1),
        ]);

        AiAgentRun::create([
            'run_uuid' => 'diag-run-1',
            'agent_key' => 'seo',
            'agent_version' => 1,
            'agent_name' => 'SEO Agent',
            'status' => 'succeeded',
            'actor_user_id' => $admin->id,
            'request_uuid' => 'diag-req-1',
            'prompt_key' => 'ai.agents.seo.v1',
            'policy_snapshot' => ['permissions' => ['use_ai_writer']],
            'budget_snapshot' => ['max_tokens' => 1800, 'max_cost' => '0.50'],
            'context' => ['site' => 'main'],
            'plan' => [],
            'steps_planned' => 1,
            'steps_completed' => 1,
            'estimated_tokens' => 50,
            'estimated_cost' => '0.00050000',
            'result' => ['status' => 'succeeded'],
            'started_at' => now()->subHours(1),
            'completed_at' => now()->subHours(1),
        ]);

        AiAgentRun::create([
            'run_uuid' => 'diag-run-2',
            'agent_key' => 'seo',
            'agent_version' => 1,
            'agent_name' => 'SEO Agent',
            'status' => 'failed',
            'actor_user_id' => $admin->id,
            'request_uuid' => 'diag-req-2',
            'prompt_key' => 'ai.agents.seo.v1',
            'policy_snapshot' => ['permissions' => ['use_ai_writer']],
            'budget_snapshot' => ['max_tokens' => 1800, 'max_cost' => '0.50'],
            'context' => ['site' => 'main'],
            'plan' => [],
            'steps_planned' => 1,
            'steps_completed' => 0,
            'estimated_tokens' => 30,
            'estimated_cost' => '0.00030000',
            'error_class' => 'RuntimeException',
            'error_message' => 'Agent stopped',
            'started_at' => now()->subMinutes(50),
            'failed_at' => now()->subMinutes(50),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai.diagnostics'));

        $response->assertStatus(200);
        $response->assertSee('AI Diagnostics');
        $response->assertSee('Usage Summary');
        $response->assertSee('Default provider');
        $response->assertSee('fake');
        $response->assertSee('Agent Layer');
        $response->assertSee('SEO Agent');
        $response->assertSee('ai.agents.seo.v1');
        $response->assertSee('Requests');
        $response->assertSee('Avg Latency');
        $response->assertSee('Tool Calls');
        $response->assertSee('Agent Runs');
        $response->assertSee('failed');
    }
}
