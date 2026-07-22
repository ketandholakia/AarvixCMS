@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)

@section('meta')
    @if($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
@endsection

@section('content')
<div class="bg-white dark:bg-gray-900 transition-colors">
    <!-- Page Header -->
    <div class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 py-16 md:py-24 text-center">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="font-heading text-4xl sm:text-5xl md:text-6xl font-bold text-gray-900 dark:text-white tracking-tight">
                {{ $page->title }}
            </h1>
        </div>
    </div>

    <!-- Page Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        @if($page->is_premium && (!auth()->check() || !auth()->user()->isPremiumSubscriber()))
            <div class="prose prose-lg prose-indigo dark:prose-invert max-w-none relative">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-white dark:to-gray-950 z-10 pointer-events-none"></div>
                <div class="opacity-30 blur-[2px] pointer-events-none select-none">
                    {!! \Illuminate\Support\Str::limit(strip_tags(app(\App\Services\BlockParser::class)->parse($page->body)), 500) !!}
                </div>
            </div>
            
            <div class="mt-8 mb-16 p-8 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-3xl text-center relative z-20">
                <div class="w-16 h-16 mx-auto bg-indigo-100 dark:bg-indigo-800 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Premium Content</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-8 max-w-lg mx-auto">This page is exclusively for our premium subscribers. Upgrade your account to get full access.</p>
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
                {!! app(\App\Services\BlockParser::class)->parse($page->body) !!}
            </div>
        @endif
    </div>
</div>
@endsection
