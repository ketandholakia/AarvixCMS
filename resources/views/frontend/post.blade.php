@extends('layouts.app')

@section('title', $post->title)

@section('content')
<article class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
    <header class="mb-14 text-center">
        @if($post->category)
            <a href="#" class="inline-block px-3 py-1 mb-6 text-sm font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 rounded-full hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                {{ $post->category->name }}
            </a>
        @endif
        
        <h1 class="font-heading text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white tracking-tight mb-6 leading-tight">
            {{ $post->title }}
        </h1>
        
        <div class="flex items-center justify-center gap-4 text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/50 dark:to-purple-900/50 flex items-center justify-center border border-indigo-200 dark:border-indigo-800">
                    <span class="text-indigo-700 dark:text-indigo-300 font-medium text-xs">
                        {{ substr($post->author->name ?? 'A', 0, 1) }}
                    </span>
                </div>
                <span class="font-medium text-gray-900 dark:text-white">{{ $post->author->name ?? 'Unknown' }}</span>
            </div>
            <span>&bull;</span>
            <time datetime="{{ $post->published_at->format('Y-m-d') }}">
                {{ $post->published_at->format('F j, Y') }}
            </time>
        </div>
    </header>

    @if($post->excerpt)
        <div class="mb-12 p-6 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border-l-4 border-indigo-500 text-lg text-gray-600 dark:text-gray-300 italic">
            {{ $post->excerpt }}
        </div>
    @endif

    <div class="prose prose-lg prose-indigo dark:prose-invert max-w-none">
        {!! $post->body !!}
    </div>
</article>
@endsection
