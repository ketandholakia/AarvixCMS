<?php

namespace Tests\Feature\Admin;

use App\AI\Providers\FakeAiProvider;
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

        $response = $this->actingAs($admin)->get(route('admin.ai.diagnostics'));

        $response->assertStatus(200);
        $response->assertSee('AI Diagnostics');
        $response->assertSee('Default provider');
        $response->assertSee('fake');
        $response->assertSee('Agent Layer');
        $response->assertSee('SEO Agent');
        $response->assertSee('ai.agents.seo.v1');
    }
}
