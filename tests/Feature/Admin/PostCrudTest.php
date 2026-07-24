<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\AiRequest;
use App\Models\Revision;
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

    public function test_admin_can_store_post_with_ai_request_uuid_and_track_revision(): void
    {
        $admin = $this->getAdmin();
        $aiRequest = AiRequest::create([
            'request_uuid' => 'post-ai-request-1',
            'feature' => 'writer',
            'status' => 'succeeded',
            'provider' => 'fake',
            'model' => 'fake-writer',
        ]);

        $body = json_encode([
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'AI-assisted post body.']],
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'AI Post',
            'slug' => 'ai-post',
            'excerpt' => 'AI-assisted excerpt',
            'body' => $body,
            'status' => 'draft',
            'ai_request_uuid' => $aiRequest->request_uuid,
        ]);

        $response->assertRedirect(route('admin.posts.index'));

        $post = Post::where('slug', 'ai-post')->firstOrFail();
        $revision = Revision::where('revisionable_type', Post::class)
            ->where('revisionable_id', $post->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($aiRequest->id, $revision->ai_request_id);
        $this->assertSame($aiRequest->request_uuid, $revision->aiRequest?->request_uuid);
    }
}
