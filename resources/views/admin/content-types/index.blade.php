@extends('layouts.admin')

@section('header', 'Content Types')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Content Types</h3>
        <p class="text-sm text-gray-500">Define custom post types and page types for your site.</p>
    </div>
    <a href="{{ route('admin.content-types.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Content Type
    </a>
</div>

<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Slug / URL Prefix</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Context</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fields</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($records as $type)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $type->name }}</div>
                        @if($type->description)
                            <div class="text-xs text-gray-500 mt-0.5">{{ $type->description }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">/{{ $type->slug }}/</code>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $type->context === 'post' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' }}">
                            {{ ucfirst($type->context) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ count($type->fields_schema ?? []) }} field(s)
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $type->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        @if($type->is_system)
                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">System</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.entries.index', ['type' => $type->slug]) }}"
                               class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium">
                                View Entries
                            </a>
                            <a href="{{ route('admin.content-types.field-builder', $type->id) }}"
                               class="text-xs text-gray-600 hover:text-gray-800 dark:text-gray-400 font-medium">
                                Fields
                            </a>
                            @unless($type->is_system)
                                <a href="{{ route('admin.content-types.edit', $type->id) }}"
                                   class="text-xs text-gray-600 hover:text-gray-800 dark:text-gray-400 font-medium">Edit</a>
                                <form action="{{ route('admin.content-types.destroy', $type->id) }}" method="POST"
                                      onsubmit="return confirm('Delete this content type?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Delete</button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        No content types defined yet. Create your first one!
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
