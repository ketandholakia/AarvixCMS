<?php

namespace Tests\Feature\Admin;

use App\AI\DTOs\AiAgentDefinition;
use App\AI\DTOs\AiAgentStep;
use App\AI\Services\AiAgentExecutionService;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AiToolSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAgentRunAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AiToolSeeder::class);
    }

    public function test_admin_can_view_agent_runs(): void
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
                    input: ['limit' => 1, 'format' => 'json'],
                    estimatedTokens: 100,
                    estimatedCost: '0.01000000',
                ),
            ],
            $admin,
            ['site' => 'main'],
        );

        $response = $this->actingAs($admin)->get(route('admin.ai-agent-runs.index'));

        $response->assertOk();
        $response->assertSee('AI Agent Runs');
        $response->assertSee('Reporting Agent');
        $response->assertSee($result['run_uuid']);

        $response = $this->actingAs($admin)->get(route('admin.ai-agent-runs.show', ['ai_agent_run' => $result['run_uuid']]));
        $response->assertOk();
        $response->assertSee('Reporting Agent');
        $response->assertSee('Plan');
        $response->assertSee('Steps');
        $response->assertSee('Resolved Policy');
        $response->assertSee('Resolved Budget');
        $response->assertSee('writer');
        $response->assertSee('1.00');
    }
}
