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
        $service = app(SettingService::class);
        $settings = [
            'site_name' => $service->get('site_name', config('app.name')),
            'site_description' => $service->get('site_description', ''),
            'social_twitter' => $service->get('social_twitter', ''),
            'social_github' => $service->get('social_github', ''),
            'ai_enabled' => $service->get('ai.enabled', config('ai.enabled', false)),
            'ai_writer_enabled' => $service->get('ai.writer.enabled', true),
            'ai_chat_enabled' => $service->get('ai.chat.enabled', true),
            'ai_image_enabled' => $service->get('ai.image.enabled', true),
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
            'ai_enabled' => ['nullable', 'boolean'],
            'ai_writer_enabled' => ['nullable', 'boolean'],
            'ai_chat_enabled' => ['nullable', 'boolean'],
            'ai_image_enabled' => ['nullable', 'boolean'],
        ]);

        $service = app(SettingService::class);
        $service->set('site_name', $data['site_name']);
        $service->set('site_description', $data['site_description'] ?? '');
        $service->set('social_twitter', $data['social_twitter'] ?? '');
        $service->set('social_github', $data['social_github'] ?? '');
        $service->set('ai.enabled', $request->boolean('ai_enabled'), 'ai', 'boolean');
        $service->set('ai.writer.enabled', $request->boolean('ai_writer_enabled'), 'ai', 'boolean');
        $service->set('ai.chat.enabled', $request->boolean('ai_chat_enabled'), 'ai', 'boolean');
        $service->set('ai.image.enabled', $request->boolean('ai_image_enabled'), 'ai', 'boolean');

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
