<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ThemeManager;
use Illuminate\Support\Facades\Artisan;

class ThemeController extends Controller
{
    public function index(ThemeManager $themeManager)
    {
        $themes = $themeManager->getAllThemes();
        return view('admin.themes.index', compact('themes'));
    }

    public function activate(Request $request, ThemeManager $themeManager)
    {
        $theme = $request->input('theme');
        
        $themeManager->setActiveTheme($theme);
        
        // Publish the assets for this theme
        Artisan::call('theme:publish', ['theme' => $theme]);
        
        // Clear page cache
        \Illuminate\Support\Facades\Cache::flush();
        
        return redirect()->back()->with('success', "Theme '{$theme}' activated successfully.");
    }
}
