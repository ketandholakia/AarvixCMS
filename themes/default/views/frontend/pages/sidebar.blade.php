@extends('layouts.app')

@section('title', $page->title)

@section('content')
<div class="bg-white dark:bg-gray-900 transition-colors">
    <!-- Page Header -->
    <div class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="font-heading text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white tracking-tight">
                {{ $page->title }}
            </h1>
        </div>
    </div>

    <!-- Page Content w/ Sidebar -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex flex-col md:flex-row gap-12">
            <!-- Main Content -->
            <div class="w-full md:w-2/3">
                <div class="prose prose-lg prose-indigo dark:prose-invert max-w-none">
                    {!! app(\App\Services\BlockParser::class)->parse($page->body) !!}
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="w-full md:w-1/3 space-y-8">
                @themeSection('sidebar')
            </aside>
        </div>
    </div>
</div>
@endsection
