<?php

namespace Tests\Unit\AI;

use App\AI\Exceptions\AiToolAuthorizationException;
use App\AI\Services\AiToolRegistryService;
use App\Models\AiTool;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiToolRegistryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(\Database\Seeders\AiToolSeeder::class);
    }

    public function test_authorize_denies_users_without_the_required_permission(): void
    {
        $tool = AiTool::query()->where('key', 'seo.propose')->firstOrFail();
        $author = User::factory()->create(['is_active' => true]);
        $author->roles()->attach(Role::where('name', 'Author')->firstOrFail());

        $this->expectException(AiToolAuthorizationException::class);

        app(AiToolRegistryService::class)->authorize($tool, $author);
    }

    public function test_record_call_stores_audit_payload_and_approval_state(): void
    {
        $tool = AiTool::query()->where('key', 'seo.propose')->firstOrFail();
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $call = app(AiToolRegistryService::class)->recordCall(
            $tool,
            ['title' => 'Example article'],
            $admin,
            ['request_uuid' => 'request-123'],
        );

        $this->assertSame('pending', $call->status);
        $this->assertSame('pending', $call->approval_state);
        $this->assertSame('request-123', $call->request_uuid);
        $this->assertSame('Example article', $call->input_payload['title']);
        $this->assertSame('seo.propose', $call->tool->key);
    }
}
