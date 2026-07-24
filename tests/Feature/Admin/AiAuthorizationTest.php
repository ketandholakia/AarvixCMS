<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_ai_permissions_are_seeded_and_attached_to_admin_role(): void
    {
        foreach ([
            'use_ai_writer',
            'use_ai_image',
            'use_ai_chat',
            'manage_ai_prompts',
            'manage_ai_providers',
            'view_ai_usage',
            'manage_ai_workflows',
        ] as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
        }

        $adminRole = Role::where('name', 'Admin')->firstOrFail();

        $this->assertTrue($adminRole->permissions()->where('name', 'manage_ai_prompts')->exists());
        $this->assertTrue($adminRole->permissions()->where('name', 'use_ai_writer')->exists());
    }

    public function test_author_cannot_access_ai_admin_routes_without_ai_permissions(): void
    {
        $author = User::factory()->create(['is_active' => true]);
        $author->roles()->attach(Role::where('name', 'Author')->firstOrFail());

        $this->actingAs($author);

        $this->get(route('admin.ai-prompts.create'))->assertStatus(403);
        $this->get(route('admin.ai.diagnostics'))->assertStatus(403);
        $this->post(route('admin.ai.writer.generate'), [])->assertStatus(403);
        $this->post(route('admin.ai.images.generate'), [])->assertStatus(403);
    }

    public function test_admin_can_access_ai_admin_routes(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $this->actingAs($admin);

        $this->get(route('admin.ai-prompts.create'))->assertOk();
        $this->get(route('admin.ai.diagnostics'))->assertOk();
    }

    public function test_ai_generation_routes_are_disabled_when_global_ai_is_off(): void
    {
        config()->set('ai.enabled', false);
        config()->set('ai.image.enabled', true);
        config()->set('ai.default_provider', 'fake');

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $this->actingAs($admin);

        $this->post(route('admin.ai.writer.generate'), [
            'context' => 'post',
            'operation' => 'rewrite',
            'document' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Draft text.']],
                ],
            ]),
        ])->assertForbidden();

        $this->postJson(route('admin.ai.images.generate'), [
            'prompt' => 'Generate a test image',
            'operation' => 'generate',
        ])->assertForbidden();
    }
}
