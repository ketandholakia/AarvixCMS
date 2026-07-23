<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = $model->generateUniqueSlug($model->title ?? $model->name ?? 'untitled');
            }
        });
    }

    public function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 2;

        while ($this->slugExists($slug)) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = method_exists($this, 'buildSlugUniquenessScope')
            ? $this->buildSlugUniquenessScope()
            : static::query();

        return $query->where('slug', $slug)->exists();
    }
}
