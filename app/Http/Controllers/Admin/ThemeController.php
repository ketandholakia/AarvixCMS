<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ThemeManager;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ThemeController extends Controller
{
    public function index(ThemeManager $themeManager, ThemeSettingsService $themeSettings)
    {
        $themes = $themeManager->getAllThemes();
        $activeTheme = $themeManager->getActiveTheme();
        $settings = $themeSettings->all($activeTheme);
        $sectionContent = [];
        $theme = $themeManager->findTheme($activeTheme) ?? [];
        $settingsSchema = $theme['settings_schema'] ?? [];
        $sections = $themeManager->getThemeSections($activeTheme);

        foreach ($sections as $section) {
            $key = $section['key'] ?? null;
            if ($key) {
                $sectionContent[$key] = $themeSettings->getSection($activeTheme, $key);
            }
        }

        return view('admin.themes.index', compact('themes', 'activeTheme', 'settings', 'settingsSchema', 'sections', 'sectionContent'));
    }

    public function activate(Request $request, ThemeManager $themeManager)
    {
        $theme = $request->validate([
            'theme' => ['required', 'string'],
        ])['theme'];

        if (! $themeManager->findTheme($theme)) {
            throw ValidationException::withMessages([
                'theme' => 'The selected theme is not installed.',
            ]);
        }

        $result = Cache::lock('theme:activate', 30)->block(5, function () use ($theme, $themeManager) {
            if (Artisan::call('theme:publish', ['theme' => $theme]) !== 0) {
                return false;
            }

            $themeManager->setActiveTheme($theme);
            Cache::forget('content_type_registry');

            return true;
        });

        if (! $result) {
            return back()
                ->withInput()
                ->with('error', "Theme '{$theme}' could not be activated.");
        }

        return redirect()->back()->with('success', "Theme '{$theme}' activated successfully.");
    }

    public function preview(Request $request, ThemeManager $themeManager)
    {
        $theme = $request->validate([
            'theme' => ['required', 'string'],
        ])['theme'];

        if (! $themeManager->findTheme($theme)) {
            throw ValidationException::withMessages([
                'theme' => 'The selected theme is not installed.',
            ]);
        }

        session(['preview_theme' => $theme]);

        return redirect()->route('home')->with('success', "Previewing theme '{$theme}'.");
    }

    public function clearPreview()
    {
        session()->forget('preview_theme');

        return redirect()->route('admin.themes.index')->with('success', 'Theme preview cleared.');
    }

    public function updateSettings(Request $request, ThemeManager $themeManager, ThemeSettingsService $themeSettings)
    {
        $activeTheme = $themeManager->getActiveTheme();
        $theme = $themeManager->findTheme($activeTheme) ?? [];
        $settingsSchema = $theme['settings_schema'] ?? [];
        $sections = $themeManager->getThemeSections($activeTheme);

        $rules = [];
        foreach ($settingsSchema as $field) {
            $key = $field['key'] ?? null;
            if (! $key) {
                continue;
            }

            $type = $field['type'] ?? 'string';
            $rules[$key] = match ($type) {
                'url' => ['nullable', 'url', 'max:2048'],
                'boolean' => ['nullable', 'boolean'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        foreach ($sections as $section) {
            $key = $section['key'] ?? null;
            if ($key) {
                $rules["sections.{$key}"] = ['nullable', 'string'];
            }
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()->toArray(),
                ], 422);
            }

            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $validator->validated();

        foreach ($settingsSchema as $field) {
            $key = $field['key'] ?? null;
            if (! $key) {
                continue;
            }

            $type = $field['type'] ?? 'string';
            $value = $type === 'boolean'
                ? $request->boolean($key)
                : ($data[$key] ?? '');

            $themeSettings->set($activeTheme, $key, $value, $type);
        }

        foreach ($sections as $section) {
            $key = $section['key'] ?? null;
            if (! $key) {
                continue;
            }

            $themeSettings->setSection($activeTheme, $key, (string) ($data['sections'][$key] ?? ''));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'theme' => $activeTheme,
            ]);
        }

        return redirect()->route('admin.themes.index')->with('success', "Theme settings updated for '{$activeTheme}'.");
    }
}
