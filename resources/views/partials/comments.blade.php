<section class="mt-16 border-t border-gray-200 pt-10">
    <h2 class="text-2xl font-semibold text-gray-900 mb-6">Comments</h2>

    @php($comments = $model->comments()->with('user')->latest()->get())

    @if($comments->isEmpty())
        <p class="text-gray-500">No comments yet.</p>
    @else
        <div class="space-y-4">
            @foreach($comments as $comment)
                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span>{{ $comment->user->name ?? 'Guest' }}</span>
                        <span>{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="mt-2 text-gray-800">
                        {{ $comment->content }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
