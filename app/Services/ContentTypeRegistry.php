<?php

namespace App\Services;

use App\Models\ContentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ContentTypeRegistry
{
    private const CACHE_KEY = 'content_type_registry';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Return all active content types (cached).
     */
    public function all(): Collection
    {
        if (!Schema::hasTable('content_types')) {
            $this->invalidate();
            return collect();
        }

        $cached = Cache::get(self::CACHE_KEY);

        if ($cached instanceof Collection) {
            return $cached;
        }

        if ($cached !== null) {
            $this->invalidate();
        }

        $fresh = ContentType::active()->orderBy('name')->get();

        Cache::put(self::CACHE_KEY, $fresh, self::CACHE_TTL);

        return $fresh;
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
