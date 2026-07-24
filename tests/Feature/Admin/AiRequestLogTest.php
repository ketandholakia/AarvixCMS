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
        AiRequest::query()->where('request_uuid', $request->request_uuid)->update([
            'created_at' => now()->subMinutes(12),
            'updated_at' => now()->subMinutes(12),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-requests.index'));

        $response->assertOk();
        $response->assertViewIs('admin.ai-requests.index');
        $response->assertSeeText('AI Requests');
        $response->assertSeeText('writer');
        $response->assertSeeText('fake-writer');
        $response->assertSeeText('Total requests');
        $response->assertSeeText('Succeeded');
        $response->assertSeeText('Failed');
        $response->assertSeeText('Avg latency');
        $response->assertSeeText('Total tokens');

        $detail = $this->actingAs($admin)->get(route('admin.ai-requests.show', $request));

        $detail->assertOk();
        $detail->assertViewIs('admin.ai-requests.show');
        $detail->assertSeeText('Request Metadata');
        $detail->assertSeeText('Response Payload');
        $detail->assertSeeText('writer.rewrite');
        $detail->assertSeeText('Queue wait');
        $detail->assertSeeText('120,000 ms');
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

    public function test_admin_can_filter_ai_requests_by_date_range(): void
    {
        $admin = $this->admin();

        AiRequest::create([
            'request_uuid' => 'req-old',
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
            'prompt_tokens' => 4,
            'completion_tokens' => 2,
            'total_tokens' => 6,
            'estimated_cost' => '0.00006000',
            'latency_ms' => 90,
            'started_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3),
        ]);

        AiRequest::create([
            'request_uuid' => 'req-recent',
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
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
        ]);

        AiRequest::query()->where('request_uuid', 'req-old')->update([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        AiRequest::query()->where('request_uuid', 'req-recent')->update([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-requests.index', [
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSeeText('req-recent');
        $response->assertDontSeeText('req-old');

        $csv = $this->actingAs($admin)->get(route('admin.ai-requests.export', [
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $csv->assertOk();
        $csv->assertSee('req-recent', false);
        $csv->assertDontSee('req-old', false);
    }

    public function test_admin_can_filter_ai_requests_by_feature_provider_and_model(): void
    {
        $admin = $this->admin();

        AiRequest::create([
            'request_uuid' => 'req-writer',
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
            'latency_ms' => 100,
            'started_at' => now()->subMinutes(15),
            'completed_at' => now()->subMinutes(14),
        ]);

        AiRequest::create([
            'request_uuid' => 'req-chat',
            'user_id' => $admin->id,
            'feature' => 'chat',
            'status' => 'failed',
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
            'latency_ms' => 220,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-requests.index', [
            'feature' => 'chat',
            'status' => 'failed',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
        ]));

        $response->assertOk();
        $response->assertSee('All features', false);
        $response->assertSee('All statuses', false);
        $response->assertSee('All providers', false);
        $response->assertSee('All models', false);
        $response->assertSee('gpt-4o-mini', false);
        $response->assertSeeText('req-chat');
        $response->assertDontSeeText('req-writer');
    }
}
