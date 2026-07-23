<?php

namespace Tests\Feature\Console;

use App\AI\Providers\FakeAiProvider;
use App\AI\Support\VectorStores\InMemoryVectorStore;
use App\Models\AiWorkflowRun;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiRefreshStaleGeneratedMetadataTest extends TestCase
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

    public function test_command_refreshes_stale_generated_metadata_for_published_posts(): void
    {
        $post = Post::factory()->create([
            'title' => 'Fresh release',
            'excerpt' => 'Initial release summary',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Initial release body.']],
                ],
            ]),
            'status' => 'published',
            'meta_title' => null,
            'meta_description' => null,
        ]);

        $service = $this->app->make(\App\Services\WorkflowService::class);
        $service->handlePublishedContent($post);

        $this->assertSame(2, AiWorkflowRun::query()
            ->where('source_type', Post::class)
            ->where('source_id', $post->id)
            ->count());

        $post->forceFill([
            'title' => 'Fresh release updated',
            'excerpt' => 'Updated release summary',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Updated release body.']],
                ],
            ]),
        ])->save();

        $this->assertTrue($service->isGeneratedMetadataStale($post));

        $exitCode = $this->artisan('ai:refresh-stale-generated-metadata', [
            '--limit' => 10,
        ]);

        $exitCode->assertExitCode(0);
        $exitCode->expectsOutputToContain('Refreshed 1 stale content item(s) across 2 workflow run(s).');

        $this->assertSame(4, AiWorkflowRun::query()
            ->where('source_type', Post::class)
            ->where('source_id', $post->id)
            ->count());

        $latestSeoRun = AiWorkflowRun::query()
            ->where('source_type', Post::class)
            ->where('source_id', $post->id)
            ->whereHas('workflow', static function ($query): void {
                $query->where('key', 'content.publish.seo-review');
            })
            ->latest('completed_at')
            ->firstOrFail();

        $this->assertSame('succeeded', $latestSeoRun->status);
        $this->assertSame('Fresh release updated', data_get($latestSeoRun->result, 'seo.meta_title'));
        $this->assertSame($post->updated_at->toISOString(), data_get($latestSeoRun->payload, 'source_updated_at'));
    }
}
