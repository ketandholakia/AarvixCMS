@extends('layouts.admin')

@section('header', 'Comments Moderation')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
    <div class="flex space-x-2">
        <a href="?status=pending" class="px-4 py-2 text-sm font-medium rounded-xl {{ $status === 'pending' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">Pending</a>
        <a href="?status=approved" class="px-4 py-2 text-sm font-medium rounded-xl {{ $status === 'approved' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">Approved</a>
        <a href="?status=spam" class="px-4 py-2 text-sm font-medium rounded-xl {{ $status === 'spam' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">Spam</a>
        <a href="?status=trash" class="px-4 py-2 text-sm font-medium rounded-xl {{ $status === 'trash' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">Trash</a>
        <a href="?status=all" class="px-4 py-2 text-sm font-medium rounded-xl {{ $status === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">All</a>
    </div>
</div>

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Author</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Comment</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">In Response To</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($comments as $comment)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white align-top">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-700 dark:text-indigo-400 font-bold text-xs">
                                {{ strtoupper(substr($comment->author_name, 0, 1)) }}
                            </div>
                            <div>
                                <div>{{ $comment->author_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                    <a href="mailto:{{ $comment->author_email }}" class="hover:underline">{{ $comment->author_email }}</a>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 max-w-md align-top">
                        <p class="line-clamp-3">{{ $comment->body }}</p>
                        <div class="mt-2 text-xs text-gray-400">
                            {{ $comment->created_at->format('M d, Y g:i A') }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-top">
                        @if($comment->commentable)
                            <a href="{{ route('post.show', $comment->commentable->slug ?? '') }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline line-clamp-2">
                                {{ $comment->commentable->title }}
                            </a>
                        @else
                            <span class="italic text-gray-400">Deleted Content</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium align-top space-x-2">
                        @if($comment->status !== 'approved')
                            <form action="{{ route('admin.comments.status', $comment->id) }}" method="POST" class="inline-block">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="text-green-600 dark:text-green-400 hover:underline">Approve</button>
                            </form>
                        @endif
                        
                        @if($comment->status !== 'spam')
                            <form action="{{ route('admin.comments.status', $comment->id) }}" method="POST" class="inline-block">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="spam">
                                <button type="submit" class="text-yellow-600 dark:text-yellow-400 hover:underline">Spam</button>
                            </form>
                        @endif

                        @if($comment->status !== 'trash')
                            <form action="{{ route('admin.comments.status', $comment->id) }}" method="POST" class="inline-block">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="trash">
                                <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">Trash</button>
                            </form>
                        @endif
                        
                        @if($comment->status === 'trash' || $comment->status === 'spam')
                            <form action="{{ route('admin.comments.destroy', $comment->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Permanently delete comment?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-800 dark:text-red-600 hover:underline ml-2">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        No comments found in this view.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($comments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $comments->links() }}
        </div>
    @endif
</div>
@endsection
