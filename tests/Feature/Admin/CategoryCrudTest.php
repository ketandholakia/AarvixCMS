<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
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

    public function test_admin_can_view_categories_index(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->getAdmin())->get(route('admin.categories.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.categories.index');
    }

    public function test_admin_can_store_category(): void
    {
        $response = $this->actingAs($this->getAdmin())->post(route('admin.categories.store'), [
            'name' => 'Tech News',
            'slug' => 'tech-news',
            'sort_order' => 1,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Tech News', 'slug' => 'tech-news']);
    }
}
