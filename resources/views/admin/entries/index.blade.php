@extends('layouts.admin')

@section('header', $contentType->name . ' Entries')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $contentType->name }} Entries</h3>
        <p class="text-sm text-gray-500">Manage all <em>{{ $contentType->name }}</em> entries.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.content-types.field-builder', $contentType->id) }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            Manage Fields
        </a>
        <a href="{{ route('admin.entries.create', ['type' => $contentType->slug]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New {{ $contentType->name }}
        </a>
    </div>
</div>

{{-- Search --}}
<form action="{{ route('admin.entries.index', ['type' => $contentType->slug]) }}" method="GET" class="mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title or slug..."
           class="w-full max-w-sm px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
</form>

<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Author</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Published</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($records as $entry)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $entry->title }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $entry->slug }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ $entry->author?->name ?? '—' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            @if($entry->status === 'published') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                            @elseif($entry->status === 'archived') bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                            @endif">
                            {{ ucfirst($entry->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ $entry->published_at?->format('M j, Y') ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ url('/' . $contentType->slug . '/' . $entry->slug) }}" target="_blank"
                               class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400">View</a>
                            <a href="{{ route('admin.entries.edit', ['type' => $contentType->slug, 'entry' => $entry->id]) }}"
                               class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium">Edit</a>
                            <form action="{{ route('admin.entries.destroy', ['type' => $contentType->slug, 'entry' => $entry->id]) }}"
                                  method="POST" onsubmit="return confirm('Delete this entry?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        No {{ $contentType->name }} entries yet.
                        <a href="{{ route('admin.entries.create', ['type' => $contentType->slug]) }}" class="text-indigo-600 hover:text-indigo-800 ml-1">Create the first one.</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($records->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
            {{ $records->links() }}
        </div>
    @endif
</div>
@endsection
