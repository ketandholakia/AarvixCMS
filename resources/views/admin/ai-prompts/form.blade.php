@extends('layouts.admin')

@section('header', $prompt->exists ? 'Edit Prompt' : 'Create Prompt')

@section('content')
<div class="max-w-5xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Prompt Details</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Every save creates a new immutable prompt version.</p>
    </div>

    <form action="{{ $prompt->exists ? route('admin.ai-prompts.update', $prompt) : route('admin.ai-prompts.store') }}" method="POST" class="space-y-0">
        @csrf
        @if($prompt->exists)
            @method('PUT')
        @endif

        <div class="p-6 grid gap-6 lg:grid-cols-2">
            <x-admin.form.input name="prompt_key" label="Prompt Key" :value="$prompt->prompt_key" required="true" help="Stable identifier such as `writer.rewrite` or `chat.search`." />
            <x-admin.form.input name="category" label="Category" :value="$prompt->category" required="true" help="Used to group prompts in the admin UI." />
            <x-admin.form.input name="title" label="Title" :value="$prompt->title" required="true" />
            <x-admin.form.input name="description" label="Description" :value="$prompt->description" />
            <div class="lg:col-span-2">
            <x-admin.form.textarea name="system_template" label="System Template" :value="$version['system_template'] ?? ''" required="true" rows="8" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Supports strict @{{variable}} placeholders.</p>
            </div>
            <div class="lg:col-span-2">
                <x-admin.form.textarea name="user_template" label="User Template" :value="$version['user_template'] ?? ''" rows="8" />
            </div>
            <x-admin.form.textarea name="variables_json" label="Variables JSON" :value="$version['variables_json'] ?? '{}'" rows="8" help="Must decode to an object or array." />
            <x-admin.form.textarea name="output_schema_json" label="Output Schema JSON" :value="$version['output_schema_json'] ?? '{}'" rows="8" help="Optional response schema for structured output." />
            <div class="lg:col-span-2">
                <x-admin.form.textarea name="change_summary" label="Change Summary" :value="$version['change_summary'] ?? ''" rows="3" />
            </div>
            <div class="lg:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="is_enabled" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('is_enabled', $prompt->is_enabled ?? true) ? 'checked' : '' }}>
                    Prompt enabled
                </label>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
            <a href="{{ $prompt->exists ? route('admin.ai-prompts.show', $prompt) : route('admin.ai-prompts.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $prompt->exists ? 'Save New Version' : 'Create Prompt' }}
            </button>
        </div>
    </form>
</div>
@endsection
