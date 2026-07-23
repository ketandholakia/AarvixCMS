<?php

namespace App\Jobs;

use App\AI\Services\ContentEmbeddingSourceResolver;
use App\Models\AiEmbeddingJob;
use App\Models\ContentEmbedding;
use App\Models\Entry;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SyncContentEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public ?string $requestUuid = null,
    ) {
    }

    public function handle(ContentEmbeddingSourceResolver $resolver): void
    {
        $source = $this->resolveSource();
        $requestUuid = $this->requestUuid ?? (string) Str::uuid();
        $job = AiEmbeddingJob::query()->firstOrCreate(
            ['request_uuid' => $requestUuid],
            [
                'source_type' => $this->sourceType,
                'source_id' => $this->sourceId,
                'queued_at' => now(),
                'status' => 'pending',
                'attempts' => 0,
            ]
        );

        $job->fill([
            'status' => 'running',
            'attempts' => $job->attempts + 1,
            'started_at' => now(),
            'last_error_class' => null,
            'last_error_message' => null,
            'payload' => [
                'source_type' => $this->sourceType,
                'source_id' => $this->sourceId,
            ],
        ])->save();

        try {
            $adapter = $resolver->resolve($source);
            $summary = $adapter->summarize($source);
            $sourceHash = (string) ($summary['chunk_hash'] ?? '');

            if ($sourceHash === '') {
                throw new RuntimeException('Embedding source produced no content hash.');
            }

            if ($job->source_hash === $sourceHash && $job->status === 'succeeded' && ContentEmbedding::query()->where([
                'source_type' => $this->sourceType,
                'source_id' => $this->sourceId,
                'chunk_index' => (int) ($summary['chunk_index'] ?? 0),
                'chunk_hash' => $sourceHash,
            ])->exists()) {
                return;
            }

            $embedding = ContentEmbedding::query()->updateOrCreate([
                'source_type' => $this->sourceType,
                'source_id' => $this->sourceId,
                'chunk_index' => (int) ($summary['chunk_index'] ?? 0),
            ], [
                'chunk_hash' => $sourceHash,
                'content_text' => (string) ($summary['content_text'] ?? ''),
                'metadata' => $summary['metadata'] ?? [],
                'vector_store' => $summary['vector_store'] ?? null,
                'vector_id' => $summary['vector_id'] ?? null,
                'visibility' => (string) ($summary['visibility'] ?? 'private'),
                'embedding_model' => $summary['embedding_model'] ?? null,
                'chunker_version' => (string) ($summary['chunker_version'] ?? '1'),
                'indexed_at' => now(),
            ]);

            ContentEmbedding::query()
                ->where('source_type', $this->sourceType)
                ->where('source_id', $this->sourceId)
                ->where('chunk_index', (int) ($summary['chunk_index'] ?? 0))
                ->where('id', '!=', $embedding->id)
                ->delete();

            $job->fill([
                'source_hash' => $sourceHash,
                'status' => 'succeeded',
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $job->fill([
                'status' => 'failed',
                'last_error_class' => class_basename($e),
                'last_error_message' => str($e->getMessage())->limit(500),
                'completed_at' => now(),
            ])->save();

            throw $e;
        }
    }

    protected function resolveSource(): Model
    {
        return match ($this->sourceType) {
            Post::class => Post::query()->findOrFail($this->sourceId),
            Page::class => Page::query()->findOrFail($this->sourceId),
            Entry::class => Entry::with('contentType')->findOrFail($this->sourceId),
            default => throw new RuntimeException('Unsupported embedding source type: ' . $this->sourceType),
        };
    }
}
