<?php

namespace Tests\Feature\AI;

use App\AI\DTOs\AiScope;
use App\AI\Services\RetrievalService;
use App\AI\Support\VectorStores\InMemoryVectorStore;
use App\Jobs\SyncContentEmbeddingsJob;
use App\Models\Page;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetrievalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        config()->set('ai.vector_store.driver', InMemoryVectorStore::class);
        config()->set('ai.vector_store.collection', 'content_embeddings');
        config()->set('ai.embeddings.chunker_version', '1');
        config()->set('ai.embeddings.model', 'text-embedding-3-small');
    }

    public function test_retrieval_only_returns_public_sources_for_regular_users(): void
    {
        $publicPost = Post::withoutEvents(function () {
            return Post::factory()->create([
                'title' => 'Launch checklist',
                'excerpt' => 'Public launch summary',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Public launch checklist for the release team.']],
                    ],
                ]),
                'status' => 'published',
            ]);
        });

        $privatePage = Page::withoutEvents(function () {
            return Page::factory()->create([
                'title' => 'Launch checklist',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Internal launch checklist and rollback notes.']],
                    ],
                ]),
                'status' => 'draft',
            ]);
        });

        app()->call([new SyncContentEmbeddingsJob(Post::class, $publicPost->id, 'retrieve-public-post'), 'handle']);
        app()->call([new SyncContentEmbeddingsJob(Page::class, $privatePage->id, 'retrieve-private-page'), 'handle']);

        $service = $this->app->make(RetrievalService::class);
        $result = $service->retrieve(new AiScope(feature: 'chat'), 'launch checklist');

        $this->assertCount(1, $result['citations']);
        $this->assertSame('Launch checklist', $result['citations'][0]['title']);
        $this->assertSame('public', $result['citations'][0]['visibility']);
        $this->assertSame(url('/blog/' . $publicPost->slug), $result['citations'][0]['public_url']);
        $this->assertSame(url('/admin/posts/' . $publicPost->id . '/edit'), $result['citations'][0]['admin_url']);
        $this->assertSame(url('/blog/' . $publicPost->slug), $result['citations'][0]['accessible_url']);
        $this->assertStringContainsString('Launch checklist', $result['answer']);
        $this->assertStringContainsString('authorized source chunk', $result['answer']);
    }

    public function test_admins_can_retrieve_private_sources_with_stable_citations(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $publicPost = Post::withoutEvents(function () {
            return Post::factory()->create([
                'title' => 'Release notes',
                'excerpt' => 'Public release summary',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Public release notes and changelog.']],
                    ],
                ]),
                'status' => 'published',
            ]);
        });

        $privatePage = Page::withoutEvents(function () {
            return Page::factory()->create([
                'title' => 'Incident response',
                'slug' => 'incident-response',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Private incident response plan and contacts.']],
                    ],
                ]),
                'status' => 'draft',
            ]);
        });

        app()->call([new SyncContentEmbeddingsJob(Post::class, $publicPost->id, 'retrieve-admin-post'), 'handle']);
        app()->call([new SyncContentEmbeddingsJob(Page::class, $privatePage->id, 'retrieve-admin-page'), 'handle']);

        $service = $this->app->make(RetrievalService::class);
        $result = $service->retrieve(new AiScope(userId: $admin->id, feature: 'chat'), 'incident response');

        $this->assertNotEmpty($result['citations']);
        $this->assertSame('Incident response', $result['citations'][0]['title']);
        $this->assertSame('private', $result['citations'][0]['visibility']);
        $this->assertSame(url('/incident-response'), $result['citations'][0]['public_url']);
        $this->assertSame(url('/admin/pages/' . $privatePage->id . '/edit'), $result['citations'][0]['admin_url']);
        $this->assertSame(url('/admin/pages/' . $privatePage->id . '/edit'), $result['citations'][0]['accessible_url']);
        $this->assertStringContainsString('Incident response', $result['context']);
    }
}
