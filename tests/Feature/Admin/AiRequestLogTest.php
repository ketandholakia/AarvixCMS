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

        $request = AiRequest::create([
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

        $detail = $this->actingAs($admin)->get(route('admin.ai-requests.show', $request));

        $detail->assertOk();
        $detail->assertViewIs('admin.ai-requests.show');
        $detail->assertSeeText('Request Metadata');
        $detail->assertSeeText('Response Payload');
        $detail->assertSeeText('writer.rewrite');
    }

    public function test_admin_can_export_ai_requests_as_csv(): void
    {
        $admin = $this->admin();

        AiRequest::create([
            'request_uuid' => 'req-123',
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

        $response = $this->actingAs($admin)->get(route('admin.ai-requests.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
        $response->assertSee('request_uuid,feature,status,provider,model,prompt_key,actor,prompt_tokens,completion_tokens,total_tokens,estimated_cost,latency_ms,created_at,completed_at', false);
        $response->assertSee('req-123', false);
        $response->assertSee('writer.rewrite', false);
    }
}
