<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasSlug, HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public static function tree()
    {
        return Cache::rememberForever('categories:tree', function () {
            $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
            $concatPath = $isSqlite 
                ? "category_tree.path || ',' || c.id" 
                : "concat(category_tree.path, ',', c.id)";
            
            $query = "
                WITH RECURSIVE category_tree AS (
                    SELECT id, parent_id, name, slug, sort_order, 0 as depth, cast(id as char(255)) as path
                    FROM categories
                    WHERE parent_id IS NULL
                    UNION ALL
                    SELECT c.id, c.parent_id, c.name, c.slug, c.sort_order, category_tree.depth + 1, {$concatPath}
                    FROM categories c
                    JOIN category_tree ON category_tree.id = c.parent_id
                )
                SELECT * FROM category_tree ORDER BY path
            ";

            return static::hydrate(\Illuminate\Support\Facades\DB::select($query));
        });
    }

    public function scopeTree($query)
    {
        return $query->whereNull('parent_id')->with('children');
    }

    protected static function booted()
    {
        static::saved(function ($category) {
            \Illuminate\Support\Facades\Cache::forget('category_tree');
            \Illuminate\Support\Facades\Cache::flush();
        });

        static::deleted(function ($category) {
            \Illuminate\Support\Facades\Cache::forget('category_tree');
            \Illuminate\Support\Facades\Cache::flush();
        });
    }
}
