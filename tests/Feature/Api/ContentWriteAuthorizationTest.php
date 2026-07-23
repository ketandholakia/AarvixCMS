<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentWriteAuthorizationTest extends TestCase
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

    protected function editorUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Editor')->first());
        return $user;
    }

    public function test_post_write_requires_model_authorization_not_just_api_ability(): void
    {
        $owner = $this->adminUser();
        $other = User::factory()->create(['is_active' => true]);

        $postId = DB::table('posts')->insertGetId([
            'author_id' => $owner->id,
            'title' => 'Owned',
            'slug' => 'owned-post',
            'body' => 'Body',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($other, ['api.write']);

        $response = $this->withoutMiddleware()->putJson("/api/v1/posts/{$postId}", [
            'title' => 'Changed',
            'body' => 'Body',
            'status' => 'draft',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_a_post_via_api(): void
    {
        $admin = $this->adminUser();
        $postId = DB::table('posts')->insertGetId([
            'author_id' => $admin->id,
            'title' => 'Owned',
            'slug' => 'owned-post-2',
            'body' => 'Body',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin, ['api.write']);
        $response = $this->withoutMiddleware()->putJson("/api/v1/posts/{$postId}", [
            'title' => 'Changed',
            'body' => 'Body',
            'status' => 'draft',
        ]);

        $response->assertStatus(200);
    }

    public function test_admin_can_manage_categories_via_api(): void
    {
        $admin = $this->adminUser();

        Sanctum::actingAs($admin, ['api.write']);
        $create = $this->withoutMiddleware()->postJson('/api/v1/categories', [
            'name' => 'News',
            'slug' => 'news',
            'description' => 'Latest',
        ]);

        $create->assertStatus(201);

        $category = Category::where('slug', 'news')->firstOrFail();

        Sanctum::actingAs($admin, ['api.write']);
        $update = $this->withoutMiddleware()->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'News Updated',
            'slug' => 'news',
            'description' => 'Latest',
        ]);

        $update->assertStatus(200);
    }

    public function test_api_category_index_renders_post_counts_without_error(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Blog',
            'slug' => 'blog',
            'description' => 'Blog',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('posts')->insert([
            'author_id' => $this->adminUser()->id,
            'category_id' => $categoryId,
            'title' => 'Hello',
            'slug' => 'hello',
            'body' => 'Body',
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->adminUser(), ['api.read']);
        $response = $this->withoutMiddleware()->getJson('/api/v1/categories');

        $response->assertStatus(200);
    }
}
