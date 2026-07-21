<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Post extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
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
        });

        static::deleted(function ($post) {
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/blog/' . $post->slug)));
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/')));
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
}
