<?php

namespace Tests\Feature\AI;

use App\Jobs\SyncContentEmbeddingsJob;
use App\Models\AiEmbeddingJob;
use App\Models\ContentEmbedding;
use App\Models\ContentType;
use App\Models\Entry;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SyncContentEmbeddingsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_posts_pages_and_entries_dispatch_embedding_jobs(): void
    {
        Bus::fake();

        $post = Post::factory()->create([
            'title' => 'Post Title',
            'excerpt' => 'Post summary',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Post body text.']],
                ],
            ]),
        ]);

        $page = Page::factory()->create([
            'title' => 'Page Title',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Page body text.']],
                ],
            ]),
        ]);

        $contentType = ContentType::factory()->create([
            'slug' => 'portfolio',
            'name' => 'Portfolio',
        ]);

        $entry = Entry::factory()->create([
            'content_type_id' => $contentType->id,
            'title' => 'Entry Title',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Entry body text.']],
                ],
            ]),
            'status' => 'published',
            'published_at' => now(),
        ]);

        Bus::assertDispatchedTimes(SyncContentEmbeddingsJob::class, 3);
        Bus::assertDispatched(SyncContentEmbeddingsJob::class, fn (SyncContentEmbeddingsJob $job) => $job->sourceType === Post::class && $job->sourceId === $post->id);
        Bus::assertDispatched(SyncContentEmbeddingsJob::class, fn (SyncContentEmbeddingsJob $job) => $job->sourceType === Page::class && $job->sourceId === $page->id);
        Bus::assertDispatched(SyncContentEmbeddingsJob::class, fn (SyncContentEmbeddingsJob $job) => $job->sourceType === Entry::class && $job->sourceId === $entry->id);
    }

    public function test_sync_content_embeddings_job_is_idempotent_and_clears_on_delete(): void
    {
        $post = Post::withoutEvents(function () {
            return Post::factory()->create([
                'title' => 'Index me',
                'excerpt' => 'Short summary',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Index body text.']],
                    ],
                ]),
                'status' => 'published',
            ]);
        });

        $job = new SyncContentEmbeddingsJob(Post::class, $post->id, 'request-embedding-1');

        app()->call([$job, 'handle']);
        app()->call([$job, 'handle']);

        $this->assertDatabaseCount('content_embeddings', 1);
        $this->assertDatabaseCount('ai_embedding_jobs', 1);

        $embedding = ContentEmbedding::query()->firstOrFail();
        $embeddingJob = AiEmbeddingJob::query()->firstOrFail();

        $this->assertSame(Post::class, $embedding->source_type);
        $this->assertSame($post->id, $embedding->source_id);
        $this->assertSame('public', $embedding->visibility);
        $this->assertSame('succeeded', $embeddingJob->status);
        $this->assertSame('request-embedding-1', $embeddingJob->request_uuid);
        $this->assertSame($embedding->chunk_hash, $embeddingJob->source_hash);
        $this->assertGreaterThanOrEqual(2, $embeddingJob->attempts);

        $post->delete();

        $this->assertDatabaseCount('content_embeddings', 0);
    }

    public function test_unpublishing_content_hides_existing_embeddings_immediately(): void
    {
        $post = Post::withoutEvents(function () {
            return Post::factory()->create([
                'title' => 'Visibility check',
                'excerpt' => 'Visibility summary',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Visibility body text.']],
                    ],
                ]),
                'status' => 'published',
            ]);
        });

        app()->call([new SyncContentEmbeddingsJob(Post::class, $post->id, 'request-visibility-1'), 'handle']);

        $this->assertDatabaseHas('content_embeddings', [
            'source_type' => Post::class,
            'source_id' => $post->id,
            'visibility' => 'public',
        ]);

        Bus::fake();

        $post->forceFill(['status' => 'draft'])->save();

        $this->assertDatabaseHas('content_embeddings', [
            'source_type' => Post::class,
            'source_id' => $post->id,
            'visibility' => 'private',
        ]);
    }
}
