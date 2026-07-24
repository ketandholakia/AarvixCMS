<?php

namespace Tests\Feature\Admin;

use App\AI\Providers\FakeAiProvider;
use App\Models\AiAgentRun;
use App\Models\AiChatRun;
use App\Models\AiConversation;
use App\Models\AiRequest;
use App\Models\AiTool;
use App\Models\AiToolCall;
use App\Models\AiWorkflow;
use App\Models\AiWorkflowRun;
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

        $workflow = AiWorkflow::create([
            'workflow_uuid' => 'diag-workflow-1',
            'key' => 'content.publish.seo-review',
            'name' => 'Publish SEO review',
            'trigger' => 'content.published',
            'version' => 1,
            'status' => 'enabled',
            'conditions' => [],
            'steps' => [],
        ]);

        AiWorkflowRun::create([
            'workflow_id' => $workflow->id,
            'run_uuid' => 'diag-workflow-run-1',
            'idempotency_key' => 'diag-workflow-idem-1',
            'trigger' => 'content.published',
            'source_type' => 'post',
            'source_id' => 1,
            'actor_user_id' => $admin->id,
            'status' => 'succeeded',
            'payload' => [],
            'result' => ['status' => 'succeeded'],
            'review_task' => [],
            'started_at' => now()->subMinutes(35),
            'completed_at' => now()->subMinutes(35),
        ]);

        AiWorkflowRun::create([
            'workflow_id' => $workflow->id,
            'run_uuid' => 'diag-workflow-run-2',
            'idempotency_key' => 'diag-workflow-idem-2',
            'trigger' => 'content.published',
            'source_type' => 'post',
            'source_id' => 2,
            'actor_user_id' => $admin->id,
            'status' => 'failed',
            'payload' => [],
            'result' => [],
            'review_task' => [],
            'error_class' => 'RuntimeException',
            'error_message' => 'Workflow stopped',
            'started_at' => now()->subMinutes(30),
            'failed_at' => now()->subMinutes(30),
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

        $conversation = AiConversation::create([
            'conversation_uuid' => 'diag-conv-1',
            'user_id' => $admin->id,
            'title' => 'Diagnostics Conversation',
            'status' => 'active',
            'scope' => ['site' => 'main'],
            'model_settings' => ['mode' => 'knowledge'],
        ]);

        AiChatRun::create([
            'conversation_id' => $conversation->id,
            'request_uuid' => 'diag-chat-1',
            'mode' => 'knowledge',
            'status' => 'succeeded',
            'question' => 'What is the public source?',
            'options' => [],
            'context' => [
                'citations' => [
                    [
                        'title' => 'Public source',
                        'source_type' => 'post',
                        'source_id' => 1,
                        'chunk_index' => 0,
                        'score' => 0.9,
                        'snippet' => 'Public citation body text.',
                    ],
                ],
                'context' => 'Citation context',
            ],
            'response_text' => 'I found 1 authorized source chunk(s) for "What is the public source?": Public source.',
            'response_metadata' => [
                'citations' => [
                    [
                        'title' => 'Public source',
                    ],
                ],
            ],
            'started_at' => now()->subMinutes(45),
            'completed_at' => now()->subMinutes(45),
        ]);

        AiChatRun::create([
            'conversation_id' => $conversation->id,
            'request_uuid' => 'diag-chat-2',
            'mode' => 'knowledge',
            'status' => 'succeeded',
            'question' => 'What is the internal incident plan?',
            'options' => [],
            'context' => [
                'citations' => [],
                'context' => 'No authorized sources matched the question.',
            ],
            'response_text' => 'I could not find any authorized sources that match "What is the internal incident plan?".',
            'response_metadata' => [
                'citations' => [],
            ],
            'started_at' => now()->subMinutes(40),
            'completed_at' => now()->subMinutes(40),
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
        $response->assertSee('RAG Summary');
        $response->assertSee('Tool-call success rate');
        $response->assertSee('Workflow success rate');
        $response->assertSee('Recent Failures');
        $response->assertSee('Provider unavailable');
        $response->assertSee('Tool execution failed');
        $response->assertSee('Agent stopped');
        $response->assertSee('failed');
        $response->assertSeeText('Retrieval turns');
        $response->assertSeeText('Cited turns');
        $response->assertSeeText('No-answer turns');
        $response->assertSeeText('No-answer rate');
        $response->assertSeeText('2');
        $response->assertSeeText('50.0%');
    }
}
