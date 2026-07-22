<div class="mt-16 pt-8 border-t border-gray-200 dark:border-gray-800" id="comments">
    <h3 class="text-2xl font-heading font-bold text-gray-900 dark:text-white mb-8">Comments</h3>

    @if(session('success'))
        <div class="p-4 mb-8 text-sm text-green-700 bg-green-100 rounded-xl dark:bg-green-900/30 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <!-- Comment Form -->
    <div class="mb-12 bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800">
        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Leave a Reply</h4>
        
        <form action="{{ route('comments.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="commentable_type" value="{{ get_class($model) }}">
            <input type="hidden" name="commentable_id" value="{{ $model->id }}">
            
            <!-- Honeypot -->
            <div style="display:none;">
                <label>Leave this empty: <input type="text" name="website_url_hp" value=""></label>
            </div>

            @guest
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="guest_name" required class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="guest_email" required class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-indigo-500">
                    </div>
                </div>
            @endguest

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comment <span class="text-red-500">*</span></label>
                <textarea name="body" rows="4" required class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-indigo-500"></textarea>
            </div>

            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-colors">
                Post Comment
            </button>
        </form>
    </div>

    <!-- Comments List -->
    @php
        $comments = \App\Models\Comment::where('commentable_type', get_class($model))
            ->where('commentable_id', $model->id)
            ->where('status', 'approved')
            ->orderBy('created_at', 'asc')
            ->get();
    @endphp
    
    <div class="space-y-8">
        @forelse($comments as $comment)
            <div class="flex gap-4">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-800 flex items-center justify-center text-gray-500 dark:text-gray-400 font-bold text-lg">
                        {{ strtoupper(substr($comment->author_name, 0, 1)) }}
                    </div>
                </div>
                <div class="flex-1">
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-4 border border-gray-200 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-2">
                            <h5 class="font-medium text-gray-900 dark:text-white">{{ $comment->author_name }}</h5>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap">{{ $comment->body }}</div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-gray-500 dark:text-gray-400 italic">No comments yet. Be the first to share your thoughts!</p>
        @endforelse
    </div>
</div>
