<?php

namespace Tests\Feature\AI;

use App\AI\DTOs\AiAgentDefinition;
use App\AI\DTOs\AiAgentStep;
use App\AI\Exceptions\AiAgentExecutionException;
use App\AI\Services\AiAgentExecutionService;
use App\Models\AiToolCall;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AiToolSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAgentExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AiToolSeeder::class);
    }

    public function test_agent_can_execute_a_read_only_plan_and_track_limits(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $agent = new AiAgentDefinition(
            key: 'reporting',
            version: 1,
            name: 'Reporting Agent',
            promptKey: 'ai.agents.documentation.v1',
            tools: ['ai.report'],
            permissions: ['view_ai_usage'],
            budgets: ['max_tokens' => 500, 'max_cost' => '1.00'],
            maxSteps: 2,
            maxSeconds: 30,
            isEnabled: true,
        );

        $result = app(AiAgentExecutionService::class)->execute(
            $agent,
            [
                new AiAgentStep(
                    toolKey: 'ai.report',
                    input: [
                        'limit' => 2,
                        'format' => 'json',
                    ],
                    estimatedTokens: 120,
                    estimatedCost: '0.01500000',
                ),
            ],
            $admin,
            ['site' => 'main'],
        );

        $this->assertSame('succeeded', $result['status']);
        $this->assertSame('reporting', $result['agent_key']);
        $this->assertSame(1, $result['completed_steps']);
        $this->assertSame(120, $result['estimated_tokens']);
        $this->assertSame('0.01500000', $result['estimated_cost']);
        $this->assertCount(1, $result['steps']);
        $this->assertSame('ai.report', $result['steps'][0]['tool_key']);

        $call = AiToolCall::query()->latest('id')->firstOrFail();
        $this->assertSame('succeeded', $call->status);
        $this->assertSame('ai.report', $call->tool->key);
    }

    public function test_agent_halts_when_step_requires_approval(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $agent = new AiAgentDefinition(
            key: 'seo',
            version: 1,
            name: 'SEO Agent',
            promptKey: 'ai.agents.seo.v1',
            tools: ['seo.propose'],
            permissions: ['edit_posts'],
            budgets: ['max_tokens' => 500, 'max_cost' => '1.00'],
            maxSteps: 2,
            maxSeconds: 30,
            isEnabled: true,
        );

        $result = app(AiAgentExecutionService::class)->execute(
            $agent,
            [
                new AiAgentStep(
                    toolKey: 'seo.propose',
                    input: [
                        'title' => 'SEO review article',
                        'content' => 'Search intent and metadata review.',
                    ],
                    estimatedTokens: 90,
                    estimatedCost: '0.01000000',
                ),
            ],
            $admin,
            ['site' => 'main'],
        );

        $this->assertSame('approval_required', $result['status']);
        $this->assertSame('seo.propose', $result['halt']['tool_key']);
        $this->assertSame(1, $result['completed_steps']);

        $call = AiToolCall::query()->latest('id')->firstOrFail();
        $this->assertSame('awaiting_approval', $call->status);
        $this->assertSame('pending', $call->approval_state);
    }

    public function test_agent_enforces_step_and_budget_limits(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $agent = new AiAgentDefinition(
            key: 'strict-reports',
            version: 1,
            name: 'Strict Reports',
            promptKey: 'ai.agents.documentation.v1',
            tools: ['ai.report'],
            permissions: ['view_ai_usage'],
            budgets: ['max_tokens' => 50, 'max_cost' => '0.01000000'],
            maxSteps: 1,
            maxSeconds: 30,
            isEnabled: true,
        );

        $this->expectException(AiAgentExecutionException::class);
        $this->expectExceptionMessage('exceeded its maximum step count');

        app(AiAgentExecutionService::class)->execute(
            $agent,
            [
                new AiAgentStep(toolKey: 'ai.report', input: ['limit' => 1], estimatedTokens: 10, estimatedCost: '0.00100000'),
                new AiAgentStep(toolKey: 'ai.report', input: ['limit' => 1], estimatedTokens: 10, estimatedCost: '0.00100000'),
            ],
            $admin,
            ['site' => 'main'],
        );
    }

    public function test_agent_rejects_plans_that_exceed_token_budget(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $agent = new AiAgentDefinition(
            key: 'token-limited',
            version: 1,
            name: 'Token Limited Agent',
            promptKey: 'ai.agents.documentation.v1',
            tools: ['ai.report'],
            permissions: ['view_ai_usage'],
            budgets: ['max_tokens' => 5, 'max_cost' => '0.10000000'],
            maxSteps: 2,
            maxSeconds: 30,
            isEnabled: true,
        );

        $this->expectException(AiAgentExecutionException::class);
        $this->expectExceptionMessage('exceeded its token budget');

        app(AiAgentExecutionService::class)->execute(
            $agent,
            [
                new AiAgentStep(toolKey: 'ai.report', input: ['limit' => 1], estimatedTokens: 6, estimatedCost: '0.00100000'),
            ],
            $admin,
            ['site' => 'main'],
        );
    }
}
