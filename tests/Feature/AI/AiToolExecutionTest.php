<?php

namespace Tests\Feature\AI;

use App\AI\Contracts\VectorStore;
use App\AI\Providers\FakeAiProvider;
use App\AI\Services\AiToolRegistryService;
use App\Models\AiToolCall;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiToolExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(\Database\Seeders\AiToolSeeder::class);
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);
        config()->set('ai.vector_store.driver', \App\AI\Support\VectorStores\InMemoryVectorStore::class);
    }

    public function test_content_search_tool_returns_authorized_search_results_and_audits_the_call(): void
    {
        $author = User::factory()->create(['is_active' => true]);
        $author->roles()->attach(Role::where('name', 'Author')->firstOrFail());

        $post = Post::factory()->create([
            'title' => 'Mars mission briefing',
            'slug' => 'mars-mission-briefing',
            'body' => 'The mission briefing covers launch windows, payload, and crew safety.',
            'status' => 'published',
        ]);

        app(VectorStore::class)->upsert(config('ai.vector_store.collection', 'content_embeddings'), [
            [
                'id' => 'post-' . $post->id . '-0',
                'vector' => app(\App\AI\Services\TextEmbeddingService::class)->vectorize('mars mission briefing launch windows payload crew safety'),
                'metadata' => [
                    'source_type' => Post::class,
                    'source_id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'visibility' => 'public',
                    'content_type' => 'post',
                    'chunk_index' => 0,
                ],
                'text' => 'The mission briefing covers launch windows, payload, and crew safety.',
            ],
        ]);

        $result = app(AiToolRegistryService::class)->execute(
            'content.search',
            [
                'query' => 'launch windows and crew safety',
                'limit' => 3,
                'source_types' => [Post::class],
            ],
            $author,
            ['site' => 'main'],
        );

        $this->assertSame($post->id, $result['citations'][0]['source_id']);
        $this->assertSame('Mars mission briefing', $result['citations'][0]['title']);
        $this->assertStringContainsString('authorized source chunk', $result['answer']);

        $call = AiToolCall::query()->latest('id')->firstOrFail();
        $this->assertSame('succeeded', $call->status);
        $this->assertSame('content.search', $call->tool->key);
        $this->assertSame(1, $call->result_summary['citation_count']);
    }

    public function test_content_summary_tool_returns_canonical_summary_and_audits_the_call(): void
    {
        $author = User::factory()->create(['is_active' => true]);
        $author->roles()->attach(Role::where('name', 'Author')->firstOrFail());

        $post = Post::factory()->create([
            'title' => 'Launch checklist',
            'slug' => 'launch-checklist',
            'excerpt' => 'A concise launch checklist for the team.',
            'body' => 'First confirm the payload. Then verify launch windows and crew safety procedures.',
            'status' => 'published',
        ]);

        $result = app(AiToolRegistryService::class)->execute(
            'content.summary',
            [
                'source_type' => Post::class,
                'source_id' => $post->id,
            ],
            $author,
            ['site' => 'main'],
        );

        $this->assertSame($post->id, $result['source_id']);
        $this->assertSame('Launch checklist', $result['title']);
        $this->assertNotEmpty($result['summary']);
        $this->assertGreaterThanOrEqual(1, $result['chunk_count']);

        $call = AiToolCall::query()->latest('id')->firstOrFail();
        $this->assertSame('succeeded', $call->status);
        $this->assertSame('content.summary', $call->tool->key);
        $this->assertSame($result['summary'], $call->result_summary['summary']);
    }

    public function test_review_required_tool_waits_for_approval_then_executes_when_approved(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $post = Post::factory()->create([
            'title' => 'SEO review article',
            'slug' => 'seo-review-article',
            'excerpt' => 'This article needs SEO review.',
            'body' => 'The body discusses content strategy, search intent, and metadata.',
            'status' => 'published',
        ]);

        $result = app(AiToolRegistryService::class)->execute(
            'seo.propose',
            [
                'title' => $post->title,
                'source_type' => Post::class,
                'source_id' => $post->id,
                'keywords' => ['content strategy', 'search intent'],
            ],
            $admin,
            ['site' => 'main'],
            $post,
        );

        $this->assertSame('approval_required', $result['status']);

        $call = AiToolCall::query()->latest('id')->firstOrFail();
        $this->assertSame('awaiting_approval', $call->status);
        $this->assertSame('pending', $call->approval_state);

        $approvedResult = app(AiToolRegistryService::class)->approveCall($call, $admin);

        $this->assertSame('SEO review article', $approvedResult['meta_title']);
        $this->assertNotEmpty($approvedResult['meta_description']);

        $call->refresh();
        $this->assertSame('succeeded', $call->status);
        $this->assertSame('approved', $call->approval_state);
        $this->assertSame($approvedResult['meta_title'], $call->result_summary['meta_title']);
        $this->assertSame($admin->id, $call->approved_by_user_id);
    }
}
