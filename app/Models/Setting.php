<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type'];

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, $default = null)
    {
        // Simple static cache to prevent N+1 queries during a single request
        static $cache = [];
        
        if (empty($cache)) {
            $cache = static::pluck('value', 'key')->toArray();
        }

        return $cache[$key] ?? $default;
    }

    protected static function booted()
    {
        static::saved(function ($setting) {
            \Illuminate\Support\Facades\Cache::flush();
        });

        static::deleted(function ($setting) {
            \Illuminate\Support\Facades\Cache::flush();
        });
    }
}
