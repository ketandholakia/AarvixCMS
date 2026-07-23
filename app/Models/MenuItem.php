<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'linkable_type',
        'linkable_id',
        'sort_order',
        'target'
    ];

    protected static function booted(): void
    {
        static::saved(function (MenuItem $item) {
            $item->flushMenuCaches();
        });

        static::deleted(function (MenuItem $item) {
            $item->flushMenuCaches();
        });
    }

    /**
     * The menu this item belongs to.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * The parent item (if this is a dropdown item).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Child items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * The model this item links to (Page, Category, etc).
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the resolved URL for the frontend.
     */
    public function getResolvedUrlAttribute(): string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->linkable) {
            if ($this->linkable_type === Page::class) {
                return route('page.show', $this->linkable->slug);
            }
            if ($this->linkable_type === Category::class) {
                return route('category.show', $this->linkable->slug);
            }
            if ($this->linkable_type === Post::class) {
                return route('post.show', $this->linkable->slug);
            }
        }

        return '#';
    }

    protected function flushMenuCaches(): void
    {
        $location = $this->menu?->location ?? $this->menu()->value('location');

        if ($location) {
            Cache::forget("menu:{$location}");
        }

        Cache::forget('menus:all');
    }
}
