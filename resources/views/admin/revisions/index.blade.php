@extends('layouts.admin')

@section('header', 'Revision History: ' . $record->title)

@section('content')
<div class="mb-6 flex justify-between items-center">
    <a href="{{ route('admin.' . \Illuminate\Support\Str::plural($type) . '.edit', $record->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Editor</a>
</div>

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Event</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($revisions as $revision)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $revision->created_at->format('M d, Y H:i:s') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $revision->user->name ?? 'System' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2 py-1 text-xs rounded font-medium 
                                {{ $revision->event === 'created' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $revision->event === 'updated' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $revision->event === 'deleted' ? 'bg-red-100 text-red-800' : '' }}
                            ">
                                {{ ucfirst($revision->event) }}
                            </span>
                            @if($revision->ai_request_id)
                                <span class="px-2 py-1 text-xs rounded font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                    AI-assisted
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.revisions.show', $revision->id) }}" class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 mr-3">View Diff</a>
                        <form action="{{ route('admin.revisions.restore', $revision->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to restore this version?');">
                            @csrf
                            <button type="submit" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Restore</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        No revisions found for this content.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($revisions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $revisions->links() }}
        </div>
    @endif
</div>
@endsection
