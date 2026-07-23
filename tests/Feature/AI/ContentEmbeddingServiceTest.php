<?php

namespace Tests\Feature\AI;

use App\AI\Services\ContentEmbeddingService;
use App\Models\ContentType;
use App\Models\Entry;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentEmbeddingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_summarizes_posts_pages_and_entries_for_indexing(): void
    {
        $service = $this->app->make(ContentEmbeddingService::class);

        $post = Post::factory()->create([
            'title' => 'Post Title',
            'excerpt' => 'Post summary',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Post body text.']],
                ],
            ]),
            'status' => 'published',
            'is_premium' => false,
        ]);

        $page = Page::factory()->create([
            'title' => 'Page Title',
            'body' => json_encode([
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => 'Page body text.']],
                ],
            ]),
            'status' => 'draft',
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

        $postSummary = $service->summarize($post);
        $pageSummary = $service->summarize($page);
        $entrySummary = $service->summarize($entry);

        $this->assertSame(Post::class, $postSummary['source_type']);
        $this->assertSame($post->id, $postSummary['source_id']);
        $this->assertSame('public', $postSummary['visibility']);
        $this->assertStringContainsString('Post Title', $postSummary['content_text']);
        $this->assertStringContainsString('Post body text.', $postSummary['content_text']);
        $this->assertStringContainsString('Post summary', $postSummary['content_text']);

        $this->assertSame(Page::class, $pageSummary['source_type']);
        $this->assertSame('private', $pageSummary['visibility']);
        $this->assertStringContainsString('Page Title', $pageSummary['content_text']);
        $this->assertStringContainsString('Page body text.', $pageSummary['content_text']);

        $this->assertSame(Entry::class, $entrySummary['source_type']);
        $this->assertSame('public', $entrySummary['visibility']);
        $this->assertSame('portfolio', $entrySummary['metadata']['content_type']);
        $this->assertSame('1', $entrySummary['chunker_version']);
        $this->assertSame(config('ai.embeddings.model'), $entrySummary['embedding_model']);
        $this->assertStringContainsString('Entry Title', $entrySummary['content_text']);
        $this->assertStringContainsString('Entry body text.', $entrySummary['content_text']);

        $this->assertSame($postSummary['chunk_hash'], $service->summarize($post)['chunk_hash']);
        $this->assertNotSame($postSummary['chunk_hash'], $pageSummary['chunk_hash']);
    }
}
