<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use App\Models\Setting;

class ThemeManager
{
    public function getActiveTheme(): string
    {
        return Setting::get('active_theme', 'default');
    }

    public function setActiveTheme(string $themeName): void
    {
        Setting::set('active_theme', $themeName);
    }

    public function getAllThemes(): array
    {
        $themesDir = base_path('themes');
        
        if (!File::exists($themesDir)) {
            File::makeDirectory($themesDir);
            return [];
        }

        $directories = File::directories($themesDir);
        $themes = [];

        foreach ($directories as $dir) {
            $themeJsonPath = $dir . '/theme.json';
            $themeName = basename($dir);
            
            if (File::exists($themeJsonPath)) {
                $info = json_decode(File::get($themeJsonPath), true);
                
                $themes[] = [
                    'id' => $themeName,
                    'name' => $info['name'] ?? ucfirst($themeName),
                    'description' => $info['description'] ?? '',
                    'version' => $info['version'] ?? '1.0.0',
                    'author' => $info['author'] ?? 'Unknown',
                    'path' => $dir,
                    'is_active' => $this->getActiveTheme() === $themeName,
                ];
            } else {
                // Support themes without a json file as a fallback
                $themes[] = [
                    'id' => $themeName,
                    'name' => ucfirst($themeName),
                    'description' => '',
                    'version' => '1.0.0',
                    'author' => 'Unknown',
                    'path' => $dir,
                    'is_active' => $this->getActiveTheme() === $themeName,
                ];
            }
        }

        return $themes;
    }
}
