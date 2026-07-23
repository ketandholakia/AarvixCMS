@extends('layouts.admin')

@section('header', 'Compare Prompt Versions')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $prompt->title }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comparing active version {{ $prompt->active_version_number }} with version {{ $version->version_number }}.</p>
        </div>
        <a href="{{ route('admin.ai-prompts.show', $prompt) }}" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Back</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Active Version {{ $activeVersion?->version_number }}</h3>
            <div class="mt-4 space-y-4 text-sm text-gray-700 dark:text-gray-300">
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">System Template</div>
                    <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-gray-50 p-4 text-xs dark:bg-gray-800">{{ $activeVersion?->system_template }}</pre>
                </div>
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">User Template</div>
                    <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-gray-50 p-4 text-xs dark:bg-gray-800">{{ $activeVersion?->user_template }}</pre>
                </div>
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">Variables</div>
                    <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-gray-50 p-4 text-xs dark:bg-gray-800">{{ json_encode($activeVersion?->variables ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Selected Version {{ $version->version_number }}</h3>
            <div class="mt-4 space-y-4 text-sm text-gray-700 dark:text-gray-300">
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">System Template</div>
                    <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-gray-50 p-4 text-xs dark:bg-gray-800">{{ $version->system_template }}</pre>
                </div>
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">User Template</div>
                    <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-gray-50 p-4 text-xs dark:bg-gray-800">{{ $version->user_template }}</pre>
                </div>
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">Variables</div>
                    <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-gray-50 p-4 text-xs dark:bg-gray-800">{{ json_encode($version->variables ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
