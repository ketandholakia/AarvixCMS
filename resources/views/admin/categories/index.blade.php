@extends('layouts.admin')

@section('header', 'Categories')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Manage Categories</h2>
        <a href="{{ route('admin.categories.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-medium">
            Create Category
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <x-admin.table :headers="['ID', 'Name', 'Parent', 'Sort Order']" :records="$records" actions="true">
        @forelse($records as $category)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">{{ $category->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                    {{ $category->name }}
                    @if($category->slug)
                        <span class="block text-xs font-normal text-gray-500">{{ $category->slug }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                    {{ $category->parent ? $category->parent->name : '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">{{ $category->sort_order }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.categories.edit', $category->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Edit</a>
                        <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this category?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No categories found. <a href="{{ route('admin.categories.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create one</a>.
                </td>
            </tr>
        @endforelse
    </x-admin.table>
</div>
@endsection
