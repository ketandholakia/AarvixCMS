<?php

namespace Tests\Feature\AI;

use App\AI\Providers\FakeAiProvider;
use App\AI\Support\VectorStores\InMemoryVectorStore;
use App\Models\AiWorkflowRun;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);
        config()->set('ai.vector_store.driver', InMemoryVectorStore::class);
        config()->set('ai.vector_store.collection', 'content_embeddings');
        config()->set('ai.models.writer.model', 'fake-writer');
    }

    public function test_publishing_a_post_creates_an_seo_review_workflow_run(): void
    {
        Queue::fake();

        $post = Post::factory()->create([
            'title' => 'Launch article',
            'excerpt' => 'Launch summary',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Launch article body text.']],
                ],
            ]),
            'meta_title' => null,
            'meta_description' => null,
            'status' => 'draft',
        ]);

        $post->forceFill([
            'status' => 'published',
        ])->save();

        $run = AiWorkflowRun::query()
            ->where('source_type', Post::class)
            ->where('source_id', $post->id)
            ->firstOrFail();

        $this->assertSame('succeeded', $run->status);
        $this->assertSame('content.publish.seo-review', $run->workflow->key);
        $this->assertSame('content.published', $run->trigger);
        $this->assertSame('Launch article', data_get($run->result, 'seo.meta_title'));
        $this->assertSame('open', data_get($run->review_task, 'status'));
        $this->assertSame('Editor', data_get($run->review_task, 'assignee_role'));
        $this->assertSame(['meta_title', 'meta_description'], $run->payload['missing_fields']);

        $this->app->make(\App\Services\WorkflowService::class)->handlePublishedContent($post);

        $this->assertSame(1, AiWorkflowRun::query()
            ->where('source_type', Post::class)
            ->where('source_id', $post->id)
            ->count());
    }

    public function test_publishing_content_with_existing_seo_skips_the_workflow(): void
    {
        Queue::fake();

        $page = Page::factory()->create([
            'title' => 'Reference page',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Reference content body.']],
                ],
            ]),
            'meta_title' => 'Reference page',
            'meta_description' => 'Reference page description',
            'status' => 'published',
        ]);

        $run = $this->app->make(\App\Services\WorkflowService::class)->handlePublishedContent($page);

        $this->assertNull($run);
        $this->assertDatabaseMissing('ai_workflow_runs', [
            'source_type' => Page::class,
            'source_id' => $page->id,
        ]);
    }
}
