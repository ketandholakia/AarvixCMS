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
        $this->assertSame(hash('sha256', $embedding->chunk_hash), $embeddingJob->source_hash);
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

    public function test_embedding_version_changes_trigger_reindexing(): void
    {
        config()->set('ai.embeddings.chunker_version', '1');
        config()->set('ai.embeddings.model', 'text-embedding-3-small');

        $post = Post::withoutEvents(function () {
            return Post::factory()->create([
                'title' => 'Versioned index',
                'excerpt' => 'Versioned summary',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Versioned body text.']],
                    ],
                ]),
                'status' => 'published',
            ]);
        });

        $job = new SyncContentEmbeddingsJob(Post::class, $post->id, 'request-version-1');

        app()->call([$job, 'handle']);

        $initialEmbedding = ContentEmbedding::query()->firstOrFail();
        $initialJob = AiEmbeddingJob::query()->firstOrFail();

        $this->assertSame('1', $initialEmbedding->chunker_version);
        $this->assertSame('text-embedding-3-small', $initialEmbedding->embedding_model);

        config()->set('ai.embeddings.chunker_version', '2');
        config()->set('ai.embeddings.model', 'text-embedding-3-large');

        app()->call([$job, 'handle']);

        $this->assertDatabaseCount('content_embeddings', 1);
        $this->assertDatabaseCount('ai_embedding_jobs', 1);

        $updatedEmbedding = ContentEmbedding::query()->firstOrFail();
        $updatedJob = AiEmbeddingJob::query()->firstOrFail();

        $this->assertNotSame($initialEmbedding->chunk_hash, $updatedEmbedding->chunk_hash);
        $this->assertSame('2', $updatedEmbedding->chunker_version);
        $this->assertSame('text-embedding-3-large', $updatedEmbedding->embedding_model);
        $this->assertSame(hash('sha256', $updatedEmbedding->chunk_hash), $updatedJob->source_hash);
        $this->assertSame('succeeded', $updatedJob->status);
        $this->assertGreaterThanOrEqual($initialJob->attempts + 1, $updatedJob->attempts);
    }

    public function test_embedding_jobs_resume_missing_chunks_without_duplication(): void
    {
        $post = Post::withoutEvents(function () {
            return Post::factory()->create([
                'title' => 'Chunked index',
                'excerpt' => 'Chunked summary',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => str_repeat('Alpha beta gamma delta epsilon zeta eta theta iota kappa. ', 40)]],
                    ],
                ]),
                'status' => 'published',
            ]);
        });

        $job = new SyncContentEmbeddingsJob(Post::class, $post->id, 'request-chunked-1');

        app()->call([$job, 'handle']);

        $embeddingCount = ContentEmbedding::query()->where('source_type', Post::class)->where('source_id', $post->id)->count();
        $this->assertGreaterThan(1, $embeddingCount);

        $removedChunk = ContentEmbedding::query()
            ->where('source_type', Post::class)
            ->where('source_id', $post->id)
            ->orderByDesc('chunk_index')
            ->firstOrFail();

        $removedChunkIndex = $removedChunk->chunk_index;
        $removedChunk->delete();

        $this->assertDatabaseMissing('content_embeddings', [
            'source_type' => Post::class,
            'source_id' => $post->id,
            'chunk_index' => $removedChunkIndex,
        ]);

        app()->call([$job, 'handle']);

        $this->assertDatabaseCount('content_embeddings', $embeddingCount);
        $this->assertDatabaseHas('content_embeddings', [
            'source_type' => Post::class,
            'source_id' => $post->id,
            'chunk_index' => $removedChunkIndex,
        ]);

        $embeddingJob = AiEmbeddingJob::query()->firstOrFail();
        $this->assertSame('succeeded', $embeddingJob->status);
        $this->assertSame($embeddingCount, count($embeddingJob->payload['completed_chunk_indices'] ?? []));
        $this->assertSame($embeddingCount, (int) ($embeddingJob->payload['chunk_count'] ?? 0));
    }
}
