<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    protected $fillable = ['name', 'location'];

    protected static function booted(): void
    {
        static::saving(function (Menu $menu) {
            $originalLocation = $menu->getOriginal('location');

            if ($originalLocation && $originalLocation !== $menu->location) {
                Cache::forget("menu:{$originalLocation}");
            }
        });

        static::saved(function (Menu $menu) {
            Cache::forget("menu:{$menu->location}");
            Cache::forget('menus:all');
        });

        static::deleted(function (Menu $menu) {
            Cache::forget("menu:{$menu->location}");
            Cache::forget('menus:all');
        });
    }

    /**
     * Get the menu items for the menu.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /**
     * Get only the root menu items (no parent).
     */
    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }
}
