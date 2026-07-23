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
            'ai_agent_seo_enabled' => $service->get('ai.agents.seo.enabled', data_get(config('ai'), 'agents.seo.is_enabled', true)),
            'ai_agent_marketing_enabled' => $service->get('ai.agents.marketing.enabled', data_get(config('ai'), 'agents.marketing.is_enabled', true)),
            'ai_agent_translation_enabled' => $service->get('ai.agents.translation.enabled', data_get(config('ai'), 'agents.translation.is_enabled', true)),
            'ai_agent_documentation_enabled' => $service->get('ai.agents.documentation.enabled', data_get(config('ai'), 'agents.documentation.is_enabled', true)),
            'ai_agent_support_enabled' => $service->get('ai.agents.support.enabled', data_get(config('ai'), 'agents.support.is_enabled', true)),
        ];

        $agentPolicies = [];

        foreach (array_keys((array) config('ai.agents', [])) as $agentKey) {
            $agentPolicies[$agentKey] = [
                'name' => data_get(config('ai'), "agents.{$agentKey}.name", ucfirst($agentKey)),
                'description' => data_get(config('ai'), "agents.{$agentKey}.description", ''),
                'enabled' => $service->get("ai.agents.{$agentKey}.enabled", data_get(config('ai'), "agents.{$agentKey}.is_enabled", true)),
                'primary_model' => $service->get("ai.agents.{$agentKey}.primary_model", data_get(config('ai'), "agents.{$agentKey}.model_policy.primary")),
                'fallback_model' => $service->get("ai.agents.{$agentKey}.fallback_model", data_get(config('ai'), "agents.{$agentKey}.model_policy.fallback")),
                'temperature' => $service->get("ai.agents.{$agentKey}.temperature", data_get(config('ai'), "agents.{$agentKey}.model_policy.temperature")),
                'max_tokens' => $service->get("ai.agents.{$agentKey}.max_tokens", data_get(config('ai'), "agents.{$agentKey}.budgets.max_tokens")),
                'max_cost' => $service->get("ai.agents.{$agentKey}.max_cost", data_get(config('ai'), "agents.{$agentKey}.budgets.max_cost")),
                'max_steps' => $service->get("ai.agents.{$agentKey}.max_steps", data_get(config('ai'), "agents.{$agentKey}.max_steps")),
                'max_seconds' => $service->get("ai.agents.{$agentKey}.max_seconds", data_get(config('ai'), "agents.{$agentKey}.max_seconds")),
            ];
        }

        return view('admin.settings.index', compact('settings', 'agentPolicies'));
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
            'ai_agent_seo_enabled' => ['nullable', 'boolean'],
            'ai_agent_marketing_enabled' => ['nullable', 'boolean'],
            'ai_agent_translation_enabled' => ['nullable', 'boolean'],
            'ai_agent_documentation_enabled' => ['nullable', 'boolean'],
            'ai_agent_support_enabled' => ['nullable', 'boolean'],
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
        $service->set('ai.agents.seo.enabled', $request->boolean('ai_agent_seo_enabled'), 'ai', 'boolean');
        $service->set('ai.agents.marketing.enabled', $request->boolean('ai_agent_marketing_enabled'), 'ai', 'boolean');
        $service->set('ai.agents.translation.enabled', $request->boolean('ai_agent_translation_enabled'), 'ai', 'boolean');
        $service->set('ai.agents.documentation.enabled', $request->boolean('ai_agent_documentation_enabled'), 'ai', 'boolean');
        $service->set('ai.agents.support.enabled', $request->boolean('ai_agent_support_enabled'), 'ai', 'boolean');

        foreach (array_keys((array) config('ai.agents', [])) as $agentKey) {
            $currentModelPolicy = data_get(config('ai'), "agents.{$agentKey}.model_policy", []);
            $currentBudgets = data_get(config('ai'), "agents.{$agentKey}.budgets", []);

            $service->set("ai.agents.{$agentKey}.enabled", $request->boolean("ai_agent_{$agentKey}_enabled"), 'ai', 'boolean');
            $service->set("ai.agents.{$agentKey}.primary_model", $request->input("ai_agent_{$agentKey}_primary_model", data_get($currentModelPolicy, 'primary')), 'ai');
            $service->set("ai.agents.{$agentKey}.fallback_model", $request->input("ai_agent_{$agentKey}_fallback_model", data_get($currentModelPolicy, 'fallback')), 'ai');
            $service->set("ai.agents.{$agentKey}.temperature", $request->input("ai_agent_{$agentKey}_temperature", data_get($currentModelPolicy, 'temperature')), 'ai');
            $service->set("ai.agents.{$agentKey}.max_tokens", $request->input("ai_agent_{$agentKey}_max_tokens", data_get($currentBudgets, 'max_tokens')), 'ai');
            $service->set("ai.agents.{$agentKey}.max_cost", $request->input("ai_agent_{$agentKey}_max_cost", data_get($currentBudgets, 'max_cost')), 'ai');
            $service->set("ai.agents.{$agentKey}.max_steps", $request->input("ai_agent_{$agentKey}_max_steps", data_get(config('ai'), "agents.{$agentKey}.max_steps")), 'ai');
            $service->set("ai.agents.{$agentKey}.max_seconds", $request->input("ai_agent_{$agentKey}_max_seconds", data_get(config('ai'), "agents.{$agentKey}.max_seconds")), 'ai');
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }
}
