@extends('layouts.admin')

@section('header', 'Prompt History')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $prompt->category }}</div>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $prompt->title }}</h2>
                <div class="mt-2 font-mono text-sm text-gray-600 dark:text-gray-300">{{ $prompt->prompt_key }}</div>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $prompt->description }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $prompt->is_enabled ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                        {{ $summary['state'] }}
                    </span>
                    <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                        Active version {{ $summary['active_version'] }}
                    </span>
                </div>
            </div>
            <div class="flex gap-3">
                <form action="{{ route('admin.ai-prompts.duplicate', $prompt) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-900/40 dark:bg-indigo-900/20 dark:text-indigo-300 dark:hover:bg-indigo-900/30">Duplicate</button>
                </form>
                <a href="{{ route('admin.ai-prompts.edit', $prompt) }}" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Edit</a>
                <a href="{{ route('admin.ai-prompts.index') }}" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Back</a>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Active version</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['active_version']) }}</div>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm dark:border-indigo-900/40 dark:bg-indigo-900/20">
            <div class="text-sm text-indigo-700 dark:text-indigo-300">Total versions</div>
            <div class="mt-2 text-3xl font-bold text-indigo-900 dark:text-indigo-100">{{ number_format($summary['total_versions']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Latest version created</div>
            <div class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">
                {{ optional($summary['latest_version_at'])->format('Y-m-d H:i') ?? 'n/a' }}
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Versions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Version</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Summary</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($prompt->versions as $version)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $version->version_number }}
                                @if($version->version_number === $prompt->active_version_number)
                                    <span class="ml-2 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300">Active</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $version->change_summary ?: 'No summary' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ optional($version->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.ai-prompts.compare', [$prompt, $version]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Compare</a>
                                    @if($version->version_number !== $prompt->active_version_number)
                                        <form action="{{ route('admin.ai-prompts.rollback', [$prompt, $version]) }}" method="POST" class="inline-block" onsubmit="return confirm('Rollback to version {{ $version->version_number }}?');">
                                            @csrf
                                            <button type="submit" class="text-amber-600 dark:text-amber-400 hover:underline">Rollback</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
