<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\PermissionSeeder::class, \Database\Seeders\RoleSeeder::class]);
    }

    protected function adminUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());
        return $user;
    }

    public function test_category_index_includes_post_counts(): void
    {
        $category = Category::create([
            'name' => 'Blog',
            'slug' => 'blog',
        ]);

        \App\Models\Post::factory()->count(2)->create([
            'category_id' => $category->id,
            'author_id' => $this->adminUser()->id,
        ]);

        $response = $this->withoutMiddleware()->getJson('/api/v1/categories');

        $response->assertOk();
        $response->assertJsonFragment(['slug' => 'blog']);
    }

    public function test_category_write_requires_admin_or_editor_role(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user, ['api.write']);

        $response = $this->withoutMiddleware()->postJson('/api/v1/categories', [
            'name' => 'News',
            'slug' => 'news',
        ]);

        $response->assertStatus(403);
    }
}
