<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type'];

    /** In-request cache: key → value, populated on first access. */
    protected static array $cache = [];

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, $default = null)
    {
        if (empty(static::$cache)) {
            static::$cache = static::pluck('value', 'key')->toArray();
        }

        return static::$cache[$key] ?? $default;
    }

    /**
     * Reset the in-memory static cache used by get().
     * Called automatically after settings are saved or deleted.
     */
    public static function clearStaticCache(): void
    {
        static::$cache = [];
    }

    protected static function booted()
    {
        static::saved(function ($setting) {
            static::clearStaticCache();
        });

        static::deleted(function ($setting) {
            static::clearStaticCache();
        });
    }
}
