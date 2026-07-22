@extends('layouts.admin')

@section('header', 'Plugins')

@section('content')
<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Installed Plugins</h3>
        <p class="text-sm text-gray-500">Drop new plugins into the <code>/plugins</code> directory.</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 uppercase border-b border-gray-100 dark:border-gray-800">
                <tr>
                    <th class="px-6 py-4 font-medium">Plugin Name</th>
                    <th class="px-6 py-4 font-medium">Namespace</th>
                    <th class="px-6 py-4 font-medium">Version</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($plugins as $plugin)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $plugin->name }}</div>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $plugin->namespace }}
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        v{{ $plugin->version }}
                    </td>
                    <td class="px-6 py-4">
                        @if($plugin->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <form action="{{ route('admin.plugins.toggle', $plugin->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-sm font-medium {{ $plugin->is_active ? 'text-red-600 hover:text-red-900 dark:text-red-400' : 'text-indigo-600 hover:text-indigo-900 dark:text-indigo-400' }}">
                                {{ $plugin->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        No plugins installed. Drop a folder into the <code>/plugins</code> directory to get started.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
