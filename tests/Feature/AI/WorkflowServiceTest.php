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
            ->whereHas('workflow', static function ($query): void {
                $query->where('key', 'content.publish.seo-review');
            })
            ->firstOrFail();

        $this->assertSame('succeeded', $run->status);
        $this->assertSame('content.publish.seo-review', $run->workflow->key);
        $this->assertSame('content.published', $run->trigger);
        $this->assertSame('Launch article', data_get($run->result, 'seo.meta_title'));
        $this->assertSame('open', data_get($run->review_task, 'status'));
        $this->assertSame('Editor', data_get($run->review_task, 'assignee_role'));
        $this->assertSame(['meta_title', 'meta_description'], $run->payload['missing_fields']);

        $socialRun = AiWorkflowRun::query()
            ->where('source_type', Post::class)
            ->where('source_id', $post->id)
            ->whereHas('workflow', static function ($query): void {
                $query->where('key', 'content.publish.social-drafts');
            })
            ->firstOrFail();

        $this->assertSame('succeeded', $socialRun->status);
        $this->assertSame('Social post variants preview', data_get($socialRun->result, 'summary'));
        $this->assertSame('x', data_get($socialRun->result, 'social_variants.0.channel'));
        $this->assertSame('open', data_get($socialRun->review_task, 'status'));
        $this->assertSame('Editor', data_get($socialRun->review_task, 'assignee_role'));
        $this->assertCount(3, data_get($socialRun->review_task, 'details.variants'));

        $this->app->make(\App\Services\WorkflowService::class)->handlePublishedContent($post);

        $this->assertSame(2, AiWorkflowRun::query()
            ->where('source_type', Post::class)
            ->where('source_id', $post->id)
            ->count());
    }

    public function test_publishing_content_with_existing_seo_still_creates_social_drafts(): void
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

        $this->assertNotNull($run);
        $this->assertSame(1, AiWorkflowRun::query()
            ->where('source_type', Page::class)
            ->where('source_id', $page->id)
            ->count());
        $this->assertTrue(AiWorkflowRun::query()
            ->where('source_type', Page::class)
            ->where('source_id', $page->id)
            ->whereHas('workflow', static function ($query): void {
                $query->where('key', 'content.publish.social-drafts');
            })
            ->exists());
        $this->assertFalse(AiWorkflowRun::query()
            ->where('source_type', Page::class)
            ->where('source_id', $page->id)
            ->whereHas('workflow', static function ($query): void {
                $query->where('key', 'content.publish.seo-review');
            })
            ->exists());
    }

    public function test_translation_request_creates_locale_specific_drafts(): void
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
            'meta_title' => 'Launch article',
            'meta_description' => 'Launch article description',
            'status' => 'published',
        ]);

        $service = $this->app->make(\App\Services\WorkflowService::class);
        $run = $service->handleTranslationRequest(
            $post,
            'Translate this article for international readers.',
            ['hi', 'gu', 'fr']
        );

        $this->assertNotNull($run);
        $this->assertSame('content.request.translation-drafts', $run->workflow->key);
        $this->assertSame(['hi', 'gu'], $run->payload['locales']);
        $this->assertSame('Translation drafts preview', data_get($run->result, 'summary'));
        $this->assertSame('Launch article (HI draft)', data_get($run->result, 'translations.hi.title'));
        $this->assertSame('Launch article (GU draft)', data_get($run->result, 'translations.gu.title'));
        $this->assertSame(['hi', 'gu'], data_get($run->review_task, 'details.locales'));
        $this->assertSame('open', data_get($run->review_task, 'status'));
        $this->assertSame('Editor', data_get($run->review_task, 'assignee_role'));

        $repeat = $service->handleTranslationRequest(
            $post,
            'Translate this article for international readers.',
            ['gu', 'hi', 'fr']
        );

        $this->assertSame($run->id, $repeat->id);
        $this->assertSame(1, AiWorkflowRun::query()
            ->where('source_type', Post::class)
            ->where('source_id', $post->id)
            ->whereHas('workflow', static function ($query): void {
                $query->where('key', 'content.request.translation-drafts');
            })
            ->count());
    }
}
