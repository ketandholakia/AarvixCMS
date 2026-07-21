@extends('layouts.admin')

@section('header', 'Posts')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Manage Posts</h2>
        <a href="{{ route('admin.posts.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-medium">
            Create Post
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <x-admin.table :headers="['ID', 'Title', 'Author', 'Category', 'Status', 'Date']" :records="$records" actions="true">
        @forelse($records as $post)
            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">{{ $post->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                    {{ $post->title }}
                    @if($post->slug)
                        <span class="block text-xs font-normal text-gray-500">{{ $post->slug }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                    {{ $post->author->name ?? 'Unknown' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                    {{ $post->category->name ?? 'Uncategorized' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($post->status === 'published')
                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-lg">Published</span>
                    @elseif($post->status === 'draft')
                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400 rounded-lg">Draft</span>
                    @else
                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg">{{ ucfirst($post->status) }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-sm">
                    {{ $post->created_at->format('M j, Y') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.posts.edit', $post->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Edit</a>
                        <form action="{{ route('admin.posts.destroy', $post->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this post?');">
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
                    No posts found. <a href="{{ route('admin.posts.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create one</a>.
                </td>
            </tr>
        @endforelse
    </x-admin.table>
</div>
@endsection
