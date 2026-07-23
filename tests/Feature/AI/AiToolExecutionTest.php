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
}
