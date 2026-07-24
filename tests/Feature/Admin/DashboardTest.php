<?php

namespace Tests\Feature\Admin;

use App\Models\AiRequest;
use App\Models\AiUsageDaily;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_active_admin_can_view_dashboard(): void
    {
        // Seed RBAC setup
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('name', 'Admin')->first();
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertSeeText('Total Posts');
    }

    public function test_admin_with_ai_usage_permission_can_view_ai_dashboard_metrics(): void
    {
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('name', 'Admin')->first();
        $admin->roles()->attach($adminRole);

        AiRequest::create([
            'request_uuid' => (string) \Illuminate\Support\Str::uuid(),
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
            'prompt_tokens' => 10,
            'completion_tokens' => 5,
            'total_tokens' => 15,
            'estimated_cost' => '0.00015000',
            'latency_ms' => 120,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        AiRequest::create([
            'request_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $admin->id,
            'feature' => 'chat',
            'status' => 'succeeded',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'prompt_key' => 'chat.search',
            'scope' => [],
            'request_metadata' => [],
            'response_metadata' => [],
            'request_payload' => [],
            'response_payload' => [],
            'prompt_tokens' => 18,
            'completion_tokens' => 9,
            'total_tokens' => 27,
            'estimated_cost' => '0.00027000',
            'latency_ms' => 210,
            'started_at' => now()->subMinutes(4),
            'completed_at' => now(),
        ]);

        AiUsageDaily::create([
            'usage_date' => now()->toDateString(),
            'user_id' => $admin->id,
            'feature' => 'writer',
            'provider' => 'fake',
            'model' => 'fake-writer',
            'requests_count' => 3,
            'prompt_tokens' => 30,
            'completion_tokens' => 12,
            'total_tokens' => 42,
            'estimated_cost' => '0.00042000',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeText('AI Usage');
        $response->assertSeeText('AI Activity Trend');
        $response->assertSeeText('Top AI Features');
        $response->assertSeeText('Provider Mix');
        $response->assertSeeText('writer');
        $response->assertSeeText('chat');
    }
}
