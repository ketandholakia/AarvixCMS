<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'cms_settings';
    private const CACHE_TTL = 86400; // 24 hours
    private const ALLOWED_KEYS = ['site_name', 'site_description', 'social_twitter', 'social_github', 'active_theme'];

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->getAll();

        if (isset($settings[$key])) {
            return $this->castValue($settings[$key]['value'], $settings[$key]['type']);
        }

        return $default;
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        if (! in_array($key, self::ALLOWED_KEYS, true)) {
            throw new \InvalidArgumentException("Unsupported setting key: {$key}");
        }

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'group' => $group,
                'type' => $type,
            ]
        );

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Setting::clearStaticCache();
    }

    /**
     * Get all settings grouped by group name.
     */
    public function getByGroup(string $group): array
    {
        $settings = $this->getAll();
        $grouped = [];

        foreach ($settings as $key => $setting) {
            if ($setting['group'] === $group) {
                $grouped[$key] = $this->castValue($setting['value'], $setting['type']);
            }
        }

        return $grouped;
    }

    /**
     * Load all settings from cache or database.
     */
    private function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Setting::all()->keyBy('key')->toArray();
        });
    }

    /**
     * Cast the value based on its stored type.
     */
    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => (string) $value,
        };
    }
}
