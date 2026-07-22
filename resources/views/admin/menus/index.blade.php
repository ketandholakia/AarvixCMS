@extends('layouts.admin')

@section('header', 'Menus')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Manage Menus</h2>
        <a href="{{ route('admin.menus.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-medium">
            Create Menu
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <x-admin.table :headers="['ID', 'Name', 'Location', 'Items', 'Created']" :records="$records" actions="true">
        @forelse($records as $menu)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">{{ $menu->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                    {{ $menu->name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                    {{ $menu->location }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                    {{ $menu->items_count }} items
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    {{ $menu->created_at->format('M j, Y') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.menus.builder', $menu->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Builder</a>
                        <a href="{{ route('admin.menus.edit', $menu->id) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">Edit Info</a>
                        <form action="{{ route('admin.menus.destroy', $menu->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this menu permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No menus found. <a href="{{ route('admin.menus.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create one</a>.
                </td>
            </tr>
        @endforelse
    </x-admin.table>
</div>
@endsection
