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

class Post extends Model
{
    use HasFactory, SoftDeletes, HasSlug, HasRevisions, \App\Traits\HasTranslations;

    public $translatable = ['title', 'excerpt', 'body', 'meta_title', 'meta_description'];

    protected $fillable = [
        'author_id',
        'is_premium',
        'category_id',
        'featured_image_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_premium' => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected static function booted()
    {
        static::saved(function ($post) {
            // Invalidate only the cached page for this specific post
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/blog/' . $post->slug)));
            // Also clear the listing pages (home, category, tag) which may reference this post
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/')));

            if ((string) $post->status !== 'published') {
                ContentEmbedding::query()
                    ->where('source_type', $post::class)
                    ->where('source_id', $post->id)
                    ->update([
                        'visibility' => 'private',
                    ]);
            }
            
            app(\App\Services\WebhookService::class)->dispatch('post.updated', $post->toArray());

            SyncContentEmbeddingsJob::dispatch($post::class, $post->id)
                ->onQueue(config('ai.queue.low', 'ai-low'))
                ->afterCommit();
        });

        static::deleted(function ($post) {
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/blog/' . $post->slug)));
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/')));
            
            app(\App\Services\WebhookService::class)->dispatch('post.deleted', ['id' => $post->id, 'slug' => $post->slug]);

            $vectorIds = ContentEmbedding::query()
                ->where('source_type', $post::class)
                ->where('source_id', $post->id)
                ->pluck('vector_id')
                ->filter()
                ->map(static fn ($vectorId) => (string) $vectorId)
                ->values()
                ->all();

            if ($vectorIds !== []) {
                app(VectorStore::class)->delete((string) config('ai.vector_store.collection', 'content_embeddings'), $vectorIds);
            }

            ContentEmbedding::query()
                ->where('source_type', $post::class)
                ->where('source_id', $post->id)
                ->delete();
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function featuredImage(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
