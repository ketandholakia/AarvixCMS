@extends('layouts.admin')

@section('header', 'AI Prompts')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Prompt Library</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Versioned prompt templates used by the AI services.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.ai-prompts.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-medium">
                Create Prompt
            </a>
            @if(!empty($filters['q']) || !empty($filters['state']))
                <a href="{{ route('admin.ai-prompts.index') }}" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors font-medium">
                    Clear Filters
                </a>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('admin.ai-prompts.index') }}" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 md:grid-cols-[1fr_220px_auto]">
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search key, title, category, description" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        <select name="state" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            <option value="">All states</option>
            <option value="enabled" {{ $filters['state'] === 'enabled' ? 'selected' : '' }}>Enabled</option>
            <option value="disabled" {{ $filters['state'] === 'disabled' ? 'selected' : '' }}>Disabled</option>
        </select>
        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button>
    </form>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total prompts</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_prompts']) }}</div>
        </div>
        <div class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm dark:border-green-900/40 dark:bg-green-900/20">
            <div class="text-sm text-green-700 dark:text-green-300">Enabled</div>
            <div class="mt-2 text-3xl font-bold text-green-900 dark:text-green-100">{{ number_format($summary['enabled_count']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Disabled</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['disabled_count']) }}</div>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm dark:border-indigo-900/40 dark:bg-indigo-900/20">
            <div class="text-sm text-indigo-700 dark:text-indigo-300">Total versions</div>
            <div class="mt-2 text-3xl font-bold text-indigo-900 dark:text-indigo-100">{{ number_format($summary['total_versions']) }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <x-admin.table :headers="['Key', 'Category', 'Title', 'Active Version', 'Versions', 'State']" :records="$prompts" actions="true">
        @forelse($prompts as $prompt)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap font-mono text-sm text-gray-700 dark:text-gray-300">{{ $prompt->prompt_key }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $prompt->category }}</td>
                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ $prompt->title }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $prompt->active_version_number }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $prompt->versions_count }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $prompt->is_enabled ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                        {{ $prompt->is_enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.ai-prompts.show', $prompt) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">History</a>
                        <a href="{{ route('admin.ai-prompts.edit', $prompt) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Edit</a>
                        <form action="{{ route('admin.ai-prompts.destroy', $prompt) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this prompt and all versions?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No prompts found. <a href="{{ route('admin.ai-prompts.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create one</a>.
                </td>
            </tr>
        @endforelse
    </x-admin.table>

    <div>
        {{ $prompts->links() }}
    </div>
</div>
@endsection
