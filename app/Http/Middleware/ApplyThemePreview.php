<?php

namespace App\Http\Middleware;

use App\Services\ThemeManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ApplyThemePreview
{
    public function handle(Request $request, Closure $next): Response
    {
        $themeManager = app(ThemeManager::class);
        $activeTheme = $themeManager->getActiveTheme();
        $previewTheme = null;

        if ($request->hasSession() && $request->session()->has('preview_theme')) {
            $previewTheme = $request->session()->get('preview_theme');

            if ($themeManager->findTheme($previewTheme)) {
                $activeTheme = $previewTheme;
            } else {
                $previewTheme = null;
            }
        }

        $themePaths = [];
        foreach ($themeManager->getThemeChain($activeTheme) as $themeName) {
            $path = base_path("themes/{$themeName}/views");
            if (is_dir($path)) {
                $themePaths[] = $path;
            }
        }

        if (! empty($themePaths)) {
            View::replaceNamespace('theme', $themePaths);
        }

        View::share([
            'themePreviewActive' => $previewTheme !== null,
            'themePreviewName' => $previewTheme,
            'themePreviewExitUrl' => route('admin.themes.preview.clear'),
        ]);

        return $next($request);
    }
}
