<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\PermissionSeeder::class, \Database\Seeders\RoleSeeder::class]);
    }

    public function test_post_policy_allows_author_to_edit_own_post(): void
    {
        $authorRole = Role::where('name', 'Author')->first();
        $authorUser = User::factory()->create();
        $authorUser->roles()->attach($authorRole);

        $post = Post::forceCreate(['author_id' => $authorUser->id, 'title' => 'Test', 'slug' => 'test']);

        $this->assertTrue($authorUser->can('update', $post));
        $this->assertFalse($authorUser->can('delete', $post)); // Authors don't have delete permission
    }

    public function test_post_policy_denies_author_from_editing_others_post(): void
    {
        $authorRole = Role::where('name', 'Author')->first();
        $authorUser = User::factory()->create();
        $authorUser->roles()->attach($authorRole);

        $otherUser = User::factory()->create();
        $post = Post::forceCreate(['author_id' => $otherUser->id, 'title' => 'Test', 'slug' => 'test-2']);

        $this->assertFalse($authorUser->can('update', $post));
        $this->assertFalse($authorUser->can('delete', $post));
    }

    public function test_post_policy_allows_editor_to_edit_any_post(): void
    {
        $editorRole = Role::where('name', 'Editor')->first();
        $editorUser = User::factory()->create();
        $editorUser->roles()->attach($editorRole);

        $otherUser = User::factory()->create();
        $post = Post::forceCreate(['author_id' => $otherUser->id, 'title' => 'Test', 'slug' => 'test-3']);

        $this->assertTrue($editorUser->can('update', $post));
        $this->assertTrue($editorUser->can('delete', $post));
    }
}
