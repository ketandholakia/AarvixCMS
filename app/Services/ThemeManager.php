<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class ThemeManager
{
    public function getThemesDirectory(): string
    {
        return base_path('themes');
    }

    public function getActiveTheme(): string
    {
        if (! Schema::hasTable('settings')) {
            return 'default';
        }

        return Setting::get('active_theme', 'default');
    }

    public function setActiveTheme(string $themeName): void
    {
        Setting::updateOrCreate(
            ['key' => 'active_theme'],
            ['value' => $themeName, 'type' => 'string']
        );

        Setting::clearStaticCache();
    }

    public function findTheme(string $themeName): ?array
    {
        return collect($this->getAllThemes())->firstWhere('id', $themeName);
    }

    public function getThemeAssetBaseUrl(string $themeName): ?string
    {
        $theme = $this->findTheme($themeName);

        return $theme['asset_base_url'] ?? null;
    }

    public function themeAssetUrl(string $themeName, string $path): string
    {
        $path = ltrim($path, '/');

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $baseUrl = $this->getThemeAssetBaseUrl($themeName);

        if ($baseUrl) {
            return rtrim($baseUrl, '/') . '/' . $path;
        }

        return asset('themes/' . $themeName . '/' . $path);
    }

    public function getThemeAssets(string $themeName): array
    {
        $theme = $this->findTheme($themeName);

        if (! $theme) {
            return ['styles' => [], 'scripts' => []];
        }

        $assets = $theme['assets'] ?? [];

        return [
            'styles' => array_values(array_filter($assets['styles'] ?? [], fn ($path) => is_string($path) && $path !== '')),
            'scripts' => array_values(array_filter($assets['scripts'] ?? [], fn ($path) => is_string($path) && $path !== '')),
        ];
    }

    public function getThemeMenuLocations(string $themeName): array
    {
        $theme = $this->findTheme($themeName);

        if (! $theme) {
            return [];
        }

        $locations = $theme['menu_locations'] ?? [];

        return array_values(array_filter($locations, function ($location) {
            return is_array($location)
                && ! empty($location['key'])
                && is_string($location['key']);
        }));
    }

    public function getThemeSections(string $themeName): array
    {
        $theme = $this->findTheme($themeName);

        if (! $theme) {
            return [];
        }

        $sections = $theme['sections'] ?? [];

        return array_values(array_filter($sections, function ($section) {
            return is_array($section)
                && ! empty($section['key'])
                && is_string($section['key']);
        }));
    }

    public function themePartPath(string $name, ?string $themeName = null): array
    {
        $themeName ??= $this->getActiveTheme();

        $candidates = [];

        foreach ($this->getThemeChain($themeName) as $theme) {
            $candidates[] = "theme::partials.{$theme}.{$name}";
        }

        $candidates[] = "theme::partials.{$name}";
        $candidates[] = "partials.{$name}";

        return $candidates;
    }

    public function themeSectionPath(string $name, ?string $themeName = null): array
    {
        $themeName ??= $this->getActiveTheme();

        $candidates = [];

        foreach ($this->getThemeChain($themeName) as $theme) {
            $candidates[] = "theme::sections.{$theme}.{$name}";
        }

        $candidates[] = "theme::sections.{$name}";
        $candidates[] = "sections.{$name}";
        $candidates[] = "partials.{$name}";

        return $candidates;
    }

    public function getThemeSectionContent(string $themeName, string $sectionName): ?string
    {
        if (! app()->bound('theme.settings')) {
            return null;
        }

        return app('theme.settings')->getSection($themeName, $sectionName);
    }

    public function themeStyles(string $themeName): array
    {
        return array_map(fn (string $path) => $this->themeAssetUrl($themeName, $path), $this->getThemeAssets($themeName)['styles']);
    }

    public function themeScripts(string $themeName): array
    {
        return array_map(fn (string $path) => $this->themeAssetUrl($themeName, $path), $this->getThemeAssets($themeName)['scripts']);
    }

    public function validateThemeManifest(array $manifest, string $themeName): array
    {
        $parent = $manifest['parent'] ?? null;

        if ($parent !== null && $parent !== '') {
            if (! is_string($parent)) {
                throw new \InvalidArgumentException("Theme '{$themeName}' has an invalid parent declaration.");
            }

            if ($parent === $themeName) {
                throw new \InvalidArgumentException("Theme '{$themeName}' cannot inherit from itself.");
            }

            if (! $this->themeDirectoryExists($parent)) {
                throw new \InvalidArgumentException("Theme '{$themeName}' references missing parent theme '{$parent}'.");
            }
        }

        return $manifest;
    }

    protected function themeDirectoryExists(string $themeName): bool
    {
        $themePath = base_path("themes/{$themeName}");

        return File::isDirectory($themePath);
    }

    public function getThemeChain(string $themeName): array
    {
        $chain = [];
        $current = $themeName;

        while ($current) {
            if (in_array($current, $chain, true)) {
                break;
            }

            $theme = $this->findTheme($current);

            if (! $theme) {
                break;
            }

            $chain[] = $current;
            $current = $theme['parent'] ?? null;
        }

        return $chain;
    }

    public function getAllThemes(): array
    {
        $themesDir = $this->getThemesDirectory();
        
        if (!File::exists($themesDir)) {
            File::makeDirectory($themesDir);
            return [];
        }

        $directories = File::directories($themesDir);
        $themes = [];
        $manifests = [];

        foreach ($directories as $dir) {
            $themeJsonPath = $dir . '/theme.json';
            $themeName = basename($dir);
            
            if (File::exists($themeJsonPath)) {
                $info = json_decode(File::get($themeJsonPath), true);
                if (! is_array($info)) {
                    $info = [];
                }
                $manifests[$themeName] = ['dir' => $dir, 'info' => $info];
            } else {
                $manifests[$themeName] = ['dir' => $dir, 'info' => []];
            }
        }

        foreach ($manifests as $themeName => $data) {
            $info = $this->validateThemeManifest($data['info'], $themeName);

            $themes[] = [
                'id' => $themeName,
                'name' => $info['name'] ?? ucfirst($themeName),
                'description' => $info['description'] ?? '',
                'version' => $info['version'] ?? '1.0.0',
                'author' => $info['author'] ?? 'Unknown',
                'parent' => $info['parent'] ?? null,
                'asset_base_url' => $info['asset_base_url'] ?? null,
                'assets' => is_array($info['assets'] ?? null) ? $info['assets'] : ['styles' => [], 'scripts' => []],
                'menu_locations' => is_array($info['menu_locations'] ?? null) ? $info['menu_locations'] : [],
                'sections' => is_array($info['sections'] ?? null) ? $info['sections'] : [],
                'settings_schema' => is_array($info['settings_schema'] ?? null) ? $info['settings_schema'] : [],
                'path' => $data['dir'],
                'is_active' => $this->getActiveTheme() === $themeName,
            ];
        }

        return $themes;
    }
}
