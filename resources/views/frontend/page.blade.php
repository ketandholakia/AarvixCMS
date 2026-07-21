@extends('layouts.app')

@section('title', $page->title)

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
        <div class="prose prose-lg prose-indigo dark:prose-invert max-w-none">
            {!! $page->body !!}
        </div>
    </div>
</div>
@endsection
