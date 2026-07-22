@extends('layouts.app')

@section('title', $page->title)

@section('content')
<div class="bg-white dark:bg-gray-900 transition-colors">
    <!-- Page Header (Full Width) -->
    <div class="bg-indigo-600 dark:bg-indigo-900 text-white py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="font-heading text-4xl sm:text-5xl md:text-6xl font-bold tracking-tight">
                {{ $page->title }}
            </h1>
        </div>
    </div>

    <!-- Page Content (Full Width) -->
    <div class="w-full">
        <div class="prose prose-lg prose-indigo dark:prose-invert max-w-none px-4 sm:px-6 lg:px-8 py-16">
            {!! $page->body !!}
        </div>
    </div>
</div>
@endsection
