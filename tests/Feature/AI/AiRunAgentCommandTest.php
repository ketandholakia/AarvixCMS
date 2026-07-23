<?php

namespace Tests\Feature\AI;

use App\Models\AiAgentRun;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AiToolSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiRunAgentCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AiToolSeeder::class);
    }

    public function test_console_command_runs_an_agent_plan_and_persists_history(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $steps = json_encode([
            [
                'tool_key' => 'ai.report',
                'input' => ['limit' => 1, 'format' => 'json'],
                'estimated_tokens' => 80,
                'estimated_cost' => '0.01000000',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->artisan('ai:agent-run', [
            'agent' => 'documentation',
            '--steps' => $steps,
            '--context' => json_encode(['site' => 'main'], JSON_THROW_ON_ERROR),
            '--actor-id' => $admin->id,
        ])->assertExitCode(0);

        $run = AiAgentRun::query()->latest('id')->firstOrFail();
        $this->assertSame('documentation', $run->agent_key);
        $this->assertSame('succeeded', $run->status);
        $this->assertNotEmpty($run->run_uuid);
        $this->assertSame($admin->id, $run->actor_user_id);
        $this->assertSame('chat', $run->policy_snapshot['primary_model']);
        $this->assertSame(2000, $run->budget_snapshot['max_tokens']);
    }
}
