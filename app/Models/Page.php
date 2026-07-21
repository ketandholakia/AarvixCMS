<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Page extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected $fillable = [
        'author_id',
        'featured_image_id',
        'title',
        'slug',
        'body',
        'template',
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
        static::saved(function ($page) {
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/' . $page->slug)));
        });

        static::deleted(function ($page) {
            \Illuminate\Support\Facades\Cache::forget('page_cache_' . md5(url('/' . $page->slug)));
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
}
