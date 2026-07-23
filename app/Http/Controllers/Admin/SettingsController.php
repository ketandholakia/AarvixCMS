<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SettingService;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'site_name' => app(SettingService::class)->get('site_name', config('app.name')),
            'site_description' => app(SettingService::class)->get('site_description', ''),
            'social_twitter' => app(SettingService::class)->get('social_twitter', ''),
            'social_github' => app(SettingService::class)->get('social_github', ''),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:1000'],
            'social_twitter' => ['nullable', 'url', 'max:255'],
            'social_github' => ['nullable', 'url', 'max:255'],
        ]);

        $service = app(SettingService::class);
        $service->set('site_name', $data['site_name']);
        $service->set('site_description', $data['site_description'] ?? '');
        $service->set('social_twitter', $data['social_twitter'] ?? '');
        $service->set('social_github', $data['social_github'] ?? '');

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
