@extends('layouts.admin')

@section('header', 'Global Settings')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Site Settings</h2>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="p-6 space-y-8">
                <!-- General Section -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-800 pb-2">General Information</h3>
                    <div class="space-y-6">
                        <x-admin.form.input 
                            name="site_name" 
                            label="Site Name" 
                            :value="$settings['site_name'] ?? config('app.name')" 
                            required="true"
                            help="The primary name of your website, displayed in the header and title tags."
                        />
                        
                        <x-admin.form.textarea 
                            name="site_description" 
                            label="Site Description (SEO)" 
                            :value="$settings['site_description'] ?? ''"
                            rows="3"
                            help="A short summary of your site for search engines."
                        />
                    </div>
                </div>

                <!-- Social Links -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-800 pb-2">Social Links</h3>
                    <div class="space-y-6">
                        <x-admin.form.input 
                            name="social_twitter" 
                            label="Twitter / X URL" 
                            type="url"
                            :value="$settings['social_twitter'] ?? ''" 
                        />
                        <x-admin.form.input 
                            name="social_github" 
                            label="GitHub URL" 
                            type="url"
                            :value="$settings['social_github'] ?? ''" 
                        />
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-800 pb-2">AI Controls</h3>
                    <div class="mb-6 grid gap-4 md:grid-cols-2">
                        <x-admin.form.select
                            name="ai_default_provider"
                            label="Default provider"
                            :value="$settings['ai_default_provider'] ?? 'fake'"
                            :options="$providerOptions"
                            help="Primary provider used for AI requests unless a feature overrides it."
                        />
                        <x-admin.form.select
                            name="ai_fallback_provider"
                            label="Fallback provider"
                            :value="$settings['ai_fallback_provider'] ?? 'fake'"
                            :options="$providerOptions"
                            help="Provider used when the selected primary provider is unavailable."
                        />
                    </div>
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50/70 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-300">
                        <div class="font-medium text-gray-900 dark:text-white">Recommended presets</div>
                        <div class="mt-2 grid gap-3 lg:grid-cols-2">
                            @foreach($providerPresets as $providerKey => $preset)
                                <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900/60">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $preset['label'] }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $preset['description'] }}</div>
                                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                                        Writer: <span class="font-mono">{{ $preset['models']['writer'] }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                        Chat: <span class="font-mono">{{ $preset['models']['chat'] }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                        Vision: <span class="font-mono">{{ $preset['models']['vision'] }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                        Image: <span class="font-mono">{{ $preset['models']['image'] }}</span>
                                    </div>
                                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                                        Use these values when this provider is your default.
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
                            <input type="checkbox" name="ai_enabled" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('ai_enabled', $settings['ai_enabled'] ?? false) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Global AI enabled</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Master kill switch for all AI requests.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
                            <input type="checkbox" name="ai_writer_enabled" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('ai_writer_enabled', $settings['ai_writer_enabled'] ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Writer enabled</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Controls content generation and rewrite tools.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
                            <input type="checkbox" name="ai_chat_enabled" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('ai_chat_enabled', $settings['ai_chat_enabled'] ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Chat enabled</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Controls conversational AI surfaces.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
                            <input type="checkbox" name="ai_image_enabled" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('ai_image_enabled', $settings['ai_image_enabled'] ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Image enabled</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Controls generation and editing utilities.</span>
                            </span>
                        </label>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <x-admin.form.input
                            name="ai_writer_model"
                            label="Writer model"
                            :value="$settings['ai_writer_model'] ?? ''"
                            help="Recommended: gpt-4.1-mini, gemini-2.5-flash, or llama3.2:3b depending on provider."
                        />
                        <x-admin.form.textarea
                            name="ai_writer_style_guide"
                            label="Writer style guide"
                            :value="$settings['ai_writer_style_guide'] ?? ''"
                            rows="4"
                            help="Optional house style instructions used by writer prompts."
                        />
                        <x-admin.form.input
                            name="ai_chat_model"
                            label="Chat model"
                            :value="$settings['ai_chat_model'] ?? ''"
                            help="Recommended: gpt-4.1-mini, gemini-2.5-flash, or llama3.2:3b depending on provider."
                        />
                        <x-admin.form.input
                            name="ai_vision_model"
                            label="Vision model"
                            :value="$settings['ai_vision_model'] ?? ''"
                            help="Recommended: gpt-4.1-mini, gemini-2.5-flash, or llava depending on provider."
                        />
                        <x-admin.form.input
                            name="ai_image_model"
                            label="Image model"
                            :value="$settings['ai_image_model'] ?? ''"
                            help="Recommended: gpt-image-1 or imagen-4.0-generate-preview. Ollama image generation is not wired yet."
                        />
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-800 pb-2">AI Agents</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
                            <input type="checkbox" name="ai_agent_seo_enabled" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('ai_agent_seo_enabled', $settings['ai_agent_seo_enabled'] ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">SEO agent enabled</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Controls the SEO-focused agent profile.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
                            <input type="checkbox" name="ai_agent_marketing_enabled" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('ai_agent_marketing_enabled', $settings['ai_agent_marketing_enabled'] ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Marketing agent enabled</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Controls the campaign and social copy agent.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
                            <input type="checkbox" name="ai_agent_translation_enabled" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('ai_agent_translation_enabled', $settings['ai_agent_translation_enabled'] ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Translation agent enabled</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Controls the localized content agent.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
                            <input type="checkbox" name="ai_agent_documentation_enabled" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('ai_agent_documentation_enabled', $settings['ai_agent_documentation_enabled'] ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Documentation agent enabled</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Controls the internal docs and reports agent.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 md:col-span-2">
                            <input type="checkbox" name="ai_agent_support_enabled" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('ai_agent_support_enabled', $settings['ai_agent_support_enabled'] ?? true) ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Support agent enabled</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400">Controls the read-only support assistant profile.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-800 pb-2">Agent Policies</h3>
                    <div class="space-y-6">
                        @foreach($agentPolicies as $agentKey => $agentPolicy)
                            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                                <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="text-base font-semibold text-gray-900 dark:text-white">{{ $agentPolicy['name'] }}</div>
                                        @if(! empty($agentPolicy['description']))
                                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $agentPolicy['description'] }}</div>
                                        @endif
                                    </div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ ($agentPolicy['enabled'] ?? false) ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                        {{ ($agentPolicy['enabled'] ?? false) ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>

                                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <x-admin.form.input
                                        :name="'ai_agent_' . $agentKey . '_primary_model'"
                                        label="Primary model"
                                        :value="$agentPolicy['primary_model'] ?? ''"
                                        help="Main model used by this agent."
                                    />
                                    <x-admin.form.input
                                        :name="'ai_agent_' . $agentKey . '_fallback_model'"
                                        label="Fallback model"
                                        :value="$agentPolicy['fallback_model'] ?? ''"
                                        help="Fallback model used when the primary model is unavailable."
                                    />
                                    <x-admin.form.input
                                        :name="'ai_agent_' . $agentKey . '_temperature'"
                                        label="Temperature"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="2"
                                        :value="$agentPolicy['temperature'] ?? ''"
                                        help="Creativity level for the agent policy."
                                    />
                                    <x-admin.form.input
                                        :name="'ai_agent_' . $agentKey . '_max_steps'"
                                        label="Max steps"
                                        type="number"
                                        min="1"
                                        :value="$agentPolicy['max_steps'] ?? ''"
                                        help="Hard cap on the number of tool calls."
                                    />
                                    <x-admin.form.input
                                        :name="'ai_agent_' . $agentKey . '_max_tokens'"
                                        label="Max tokens"
                                        type="number"
                                        min="1"
                                        :value="$agentPolicy['max_tokens'] ?? ''"
                                        help="Estimated token budget for the plan."
                                    />
                                    <x-admin.form.input
                                        :name="'ai_agent_' . $agentKey . '_max_cost'"
                                        label="Max cost"
                                        type="text"
                                        :value="$agentPolicy['max_cost'] ?? ''"
                                        help="Decimal cost ceiling for the plan."
                                    />
                                    <x-admin.form.input
                                        :name="'ai_agent_' . $agentKey . '_max_seconds'"
                                        label="Max seconds"
                                        type="number"
                                        min="1"
                                        :value="$agentPolicy['max_seconds'] ?? ''"
                                        help="Wall-clock time limit for execution."
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
                <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
