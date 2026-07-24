<?php

namespace Tests\Feature\Admin;

use App\AI\Providers\FakeAiProvider;
use App\Models\AiRequest;
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
    }
}
