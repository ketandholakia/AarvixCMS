<?php

namespace App\Services;

use App\Models\ContentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ContentTypeRegistry
{
    private const CACHE_KEY = 'content_type_registry';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Return all active content types (cached).
     */
    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return ContentType::active()->orderBy('name')->get();
        });
    }

    /**
     * Return active content types filtered by context ('post' or 'page').
     */
    public function ofContext(string $context): Collection
    {
        return $this->all()->where('context', $context)->values();
    }

    /**
     * Find a single active type by its slug. Returns null if not found.
     */
    public function find(string $slug): ?ContentType
    {
        return $this->all()->firstWhere('slug', $slug);
    }

    /**
     * Clear the cached registry (call when a type is created/updated/deleted).
     */
    public function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
