<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_admin_can_access_restricted_routes()
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'Admin')->first());

        $this->actingAs($admin);

        $this->get(route('admin.settings.index'))->assertStatus(200);
        $this->get(route('admin.users.index'))->assertStatus(200);
        $this->get(route('admin.themes.index'))->assertStatus(200);
    }

    public function test_author_cannot_access_restricted_routes()
    {
        $author = User::factory()->create();
        $author->roles()->attach(Role::where('name', 'Author')->first());

        $this->actingAs($author);

        // Author should be able to view posts
        $this->get(route('admin.posts.index'))->assertStatus(200);

        // Author should NOT be able to view users, settings, themes, roles, plugins
        $this->get(route('admin.settings.index'))->assertStatus(403);
        $this->get(route('admin.users.index'))->assertStatus(403);
        $this->get(route('admin.themes.index'))->assertStatus(403);
        $this->get(route('admin.roles.index'))->assertStatus(403);
        $this->get(route('admin.plugins.index'))->assertStatus(403);
    }
}
