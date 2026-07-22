@extends('layouts.admin')

@section('header', 'Webhooks')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <p class="text-sm text-gray-500 dark:text-gray-400">Manage external HTTP webhooks triggered on content changes.</p>
    <a href="{{ route('admin.webhooks.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors">Add Webhook</a>
</div>

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">URL</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($webhooks as $webhook)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {{ $webhook->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $webhook->url }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($webhook->is_active)
                            <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded text-xs font-medium">Active</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400 rounded text-xs font-medium">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.webhooks.edit', $webhook->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">Edit</a>
                        <form action="{{ route('admin.webhooks.destroy', $webhook->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this webhook?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        No webhooks configured.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($webhooks->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $webhooks->links() }}
        </div>
    @endif
</div>
@endsection
