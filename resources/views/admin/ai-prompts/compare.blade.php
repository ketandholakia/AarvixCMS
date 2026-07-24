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

    <div class="grid gap-4 md:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Active version</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $prompt->active_version_number }}</div>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm dark:border-indigo-900/40 dark:bg-indigo-900/20">
            <div class="text-sm text-indigo-700 dark:text-indigo-300">Selected version</div>
            <div class="mt-2 text-3xl font-bold text-indigo-900 dark:text-indigo-100">{{ $version->version_number }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">System template</div>
            <div class="mt-2 text-sm font-semibold {{ $comparison['system_template_changed'] ? 'text-amber-700 dark:text-amber-300' : 'text-green-700 dark:text-green-300' }}">
                {{ $comparison['system_template_changed'] ? 'Changed' : 'Unchanged' }}
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Variables</div>
            <div class="mt-2 text-sm font-semibold {{ $comparison['variables_changed'] ? 'text-amber-700 dark:text-amber-300' : 'text-green-700 dark:text-green-300' }}">
                {{ $comparison['variables_changed'] ? 'Changed' : 'Unchanged' }}
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Output schema</div>
            <div class="mt-2 text-sm font-semibold {{ $comparison['output_schema_changed'] ? 'text-amber-700 dark:text-amber-300' : 'text-green-700 dark:text-green-300' }}">
                {{ $comparison['output_schema_changed'] ? 'Changed' : 'Unchanged' }}
            </div>
        </div>
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
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">Change Summary</div>
                    <p class="mt-2 rounded-xl bg-gray-50 p-4 text-xs dark:bg-gray-800">{{ $activeVersion?->change_summary ?: 'No summary' }}</p>
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
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">Change Summary</div>
                    <p class="mt-2 rounded-xl bg-gray-50 p-4 text-xs dark:bg-gray-800">{{ $version->change_summary ?: 'No summary' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Comparison Details</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Field</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach([
                        'System template' => $comparison['system_template_changed'],
                        'User template' => $comparison['user_template_changed'],
                        'Variables' => $comparison['variables_changed'],
                        'Output schema' => $comparison['output_schema_changed'],
                        'Change summary' => $comparison['change_summary_changed'],
                    ] as $field => $changed)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $field }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $changed ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' }}">
                                    {{ $changed ? 'Changed' : 'Unchanged' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
