<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSlug;
use App\Traits\HasRevisions;
use App\Jobs\SyncContentEmbeddingsJob;
use App\Models\ContentEmbedding;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Page extends Model
{
    use HasFactory, HasSlug, SoftDeletes, HasRevisions, \App\Traits\HasTranslations;

    public $translatable = ['title', 'body', 'meta_title', 'meta_description'];

    protected $fillable = [
        'author_id',
        'is_premium',
        'featured_image_id',
        'title',
        'slug',
        'body',
        'template',
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
        static::saved(function ($page) {
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/' . $page->slug)));
            app(\App\Services\WebhookService::class)->dispatch('page.updated', $page->toArray());

            SyncContentEmbeddingsJob::dispatch($page::class, $page->id)
                ->onQueue(config('ai.queue.low', 'ai-low'))
                ->afterCommit();
        });

        static::deleted(function ($page) {
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/' . $page->slug)));
            app(\App\Services\WebhookService::class)->dispatch('page.deleted', ['id' => $page->id, 'slug' => $page->slug]);

            ContentEmbedding::query()
                ->where('source_type', $page::class)
                ->where('source_id', $page->id)
                ->delete();
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
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
