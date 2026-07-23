<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSlug;
use App\Traits\HasRevisions;
use App\Jobs\SyncContentEmbeddingsJob;
use App\Models\ContentEmbedding;
use App\AI\Contracts\VectorStore;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Database\Factories\EntryFactory;

class Entry extends Model
{
    use HasFactory, SoftDeletes, HasSlug, HasRevisions;

    protected static function newFactory(): EntryFactory
    {
        return EntryFactory::new();
    }
    protected $fillable = [
        'content_type_id',
        'author_id',
        'featured_image_id',
        'category_id',
        'title',
        'slug',
        'body',
        'custom_fields',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'template',
    ];

    protected $casts = [
        'published_at'  => 'datetime',
        'custom_fields' => 'array',
    ];

    // ─── Slug uniqueness scope ─────────────────────────────────────────────────
    // Slugs are unique per content type, not globally.

    protected function buildSlugUniquenessScope(): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('content_type_id', $this->content_type_id);
    }

    // ─── Cache invalidation ────────────────────────────────────────────────────

    protected static function booted()
    {
        static::saved(function (Entry $entry) {
            $prefix = $entry->contentType?->slug ?? 'entry';
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url("/{$prefix}/{$entry->slug}")));

            if ((string) $entry->status !== 'published') {
                ContentEmbedding::query()
                    ->where('source_type', $entry::class)
                    ->where('source_id', $entry->id)
                    ->update([
                        'visibility' => 'private',
                    ]);
            }

            SyncContentEmbeddingsJob::dispatch($entry::class, $entry->id)
                ->onQueue(config('ai.queue.low', 'ai-low'))
                ->afterCommit();
        });

        static::deleted(function (Entry $entry) {
            $prefix = $entry->contentType?->slug ?? 'entry';
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url("/{$prefix}/{$entry->slug}")));

            $vectorIds = ContentEmbedding::query()
                ->where('source_type', $entry::class)
                ->where('source_id', $entry->id)
                ->pluck('vector_id')
                ->filter()
                ->map(static fn ($vectorId) => (string) $vectorId)
                ->values()
                ->all();

            if ($vectorIds !== []) {
                app(VectorStore::class)->delete((string) config('ai.vector_store.collection', 'content_embeddings'), $vectorIds);
            }

            ContentEmbedding::query()
                ->where('source_type', $entry::class)
                ->where('source_id', $entry->id)
                ->delete();
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Read a single value from the custom_fields JSON by key.
     */
    public function getCustomField(string $key, mixed $default = null): mixed
    {
        return data_get($this->custom_fields, $key, $default);
    }
}
