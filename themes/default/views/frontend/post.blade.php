@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)

@section('meta')
    @if($post->meta_description)
        <meta name="description" content="{{ $post->meta_description }}">
    @elseif($post->excerpt)
        <meta name="description" content="{{ $post->excerpt }}">
    @endif
@endsection

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

    @if($post->is_premium && (!auth()->check() || !auth()->user()->isPremiumSubscriber()))
        <div class="prose prose-lg prose-indigo dark:prose-invert max-w-none relative">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-white dark:to-gray-950 z-10 pointer-events-none"></div>
            <div class="opacity-30 blur-[2px] pointer-events-none select-none">
                {!! \Illuminate\Support\Str::limit(strip_tags(app(\App\Services\BlockParser::class)->parse($post->body)), 500) !!}
            </div>
        </div>
        
        <div class="mt-8 mb-16 p-8 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-3xl text-center relative z-20">
            <div class="w-16 h-16 mx-auto bg-indigo-100 dark:bg-indigo-800 rounded-full flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Premium Content</h3>
            <p class="text-gray-600 dark:text-gray-300 mb-8 max-w-lg mx-auto">This article is exclusively for our premium subscribers. Upgrade your account to get full access to this and all other premium articles.</p>
            @if(auth()->check())
                <a href="#" class="inline-flex justify-center px-8 py-3 text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">
                    Upgrade to Premium
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex justify-center px-8 py-3 text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">
                    Log in to read
                </a>
            @endif
        </div>
    @else
        <div class="prose prose-lg prose-indigo dark:prose-invert max-w-none">
            {!! app(\App\Services\BlockParser::class)->parse($post->body) !!}
        </div>
    @endif
    
    @include('partials.comments', ['model' => $post])
</article>
@endsection
