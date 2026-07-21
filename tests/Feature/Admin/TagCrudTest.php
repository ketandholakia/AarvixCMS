<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function getAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('slug', 'admin')->first();
        $admin->roles()->attach($adminRole);

        return $admin;
    }

    public function test_admin_can_view_tags_index(): void
    {
        Tag::factory()->count(3)->create();

        $response = $this->actingAs($this->getAdmin())->get(route('admin.tags.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.index');
    }

    public function test_admin_can_store_tag(): void
    {
        $response = $this->actingAs($this->getAdmin())->post(route('admin.tags.store'), [
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $response->assertRedirect(route('admin.tags.index'));
        $this->assertDatabaseHas('tags', ['name' => 'Laravel', 'slug' => 'laravel']);
    }
}
