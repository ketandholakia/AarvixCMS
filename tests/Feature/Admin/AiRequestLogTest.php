<?php

namespace Tests\Feature\Admin;

use App\Models\AiRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiRequestLogTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_admin_can_view_ai_request_log(): void
    {
        $admin = $this->admin();

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
            'prompt_tokens' => 12,
            'completion_tokens' => 8,
            'total_tokens' => 20,
            'estimated_cost' => '0.00020000',
            'latency_ms' => 180,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-requests.index'));

        $response->assertOk();
        $response->assertViewIs('admin.ai-requests.index');
        $response->assertSeeText('AI Requests');
        $response->assertSeeText('writer');
        $response->assertSeeText('fake-writer');
    }
}
