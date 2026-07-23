<?php

namespace App\Services;

use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    private const CACHE_PREFIX = 'theme_settings:';
    private const ALLOWED_KEYS = ['logo_url', 'accent_color', 'font_family', 'dark_mode_enabled'];

    public function get(string $theme, string $key, mixed $default = null): mixed
    {
        $settings = $this->all($theme);

        return $settings[$key] ?? $default;
    }

    public function set(string $theme, string $key, mixed $value, string $type = 'string'): void
    {
        if (! $this->isAllowedKey($key)) {
            throw new \InvalidArgumentException("Unsupported theme setting key: {$key}");
        }

        if (is_bool($value) && $type === 'string') {
            $type = 'boolean';
        }

        ThemeSetting::updateOrCreate(
            ['theme' => $theme, 'key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value, 'type' => $type]
        );

        Cache::forget(self::CACHE_PREFIX . $theme);
    }

    public function setSection(string $theme, string $section, string $content): void
    {
        $this->set($theme, "section:{$section}", $this->sanitizeSectionHtml($content), 'html');
    }

    public function getSection(string $theme, string $section, string $default = ''): string
    {
        return (string) $this->get($theme, "section:{$section}", $default);
    }

    public function all(string $theme): array
    {
        return Cache::rememberForever(self::CACHE_PREFIX . $theme, function () use ($theme) {
            return ThemeSetting::where('theme', $theme)->get()
                ->mapWithKeys(fn ($setting) => [$setting->key => $this->castValue($setting->value, $setting->type)])
                ->all();
        });
    }

    protected function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => (string) $value,
        };
    }

    protected function isAllowedKey(string $key): bool
    {
        return in_array($key, self::ALLOWED_KEYS, true) || str_starts_with($key, 'section:');
    }

    protected function sanitizeSectionHtml(string $content): string
    {
        if (function_exists('clean')) {
            return clean($content, 'default');
        }

        return strip_tags($content);
    }
}
