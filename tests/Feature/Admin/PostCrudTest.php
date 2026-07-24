<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function getAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('name', 'Admin')->first();
        $admin->roles()->attach($adminRole);

        return $admin;
    }

    public function test_admin_can_view_posts_index(): void
    {
        Post::factory()->count(3)->create();

        $response = $this->actingAs($this->getAdmin())->get(route('admin.posts.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.posts.index');
    }

    public function test_admin_can_store_post(): void
    {
        $body = json_encode([
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'This is the body content.']],
            ],
        ]);

        $response = $this->actingAs($this->getAdmin())->post(route('admin.posts.store'), [
            'title' => 'My First Post',
            'slug' => 'my-first-post',
            'excerpt' => 'This is an excerpt',
            'body' => $body,
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseHas('posts', ['title' => 'My First Post', 'slug' => 'my-first-post', 'body' => $body]);
    }
}
