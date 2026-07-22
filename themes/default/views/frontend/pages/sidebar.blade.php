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
                <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-heading text-lg font-bold text-gray-900 dark:text-white mb-4">About Us</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        This is a custom sidebar widget area. You can put custom content, recent posts, or author bios here.
                    </p>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-heading text-lg font-bold text-gray-900 dark:text-white mb-4">Categories</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach(\App\Models\Category::take(5)->get() as $cat)
                        <li>
                            <a href="{{ route('category.show', $cat->slug) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $cat->name }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
