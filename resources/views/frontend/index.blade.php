@extends('layouts.app')

@section('title', 'Blog')

@section('content')
<!-- Hero Section -->
<section class="relative overflow-hidden bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 transition-colors">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiM2MzY2ZjEiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] [mask-image:linear-gradient(to_bottom,white,transparent)] dark:[mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-20 relative z-10 text-center">
        <h1 class="font-heading text-5xl md:text-6xl font-bold tracking-tight text-gray-900 dark:text-white mb-6">
            Welcome to <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600 dark:from-indigo-400 dark:to-violet-400">{{ \App\Models\Setting::get('site_name', 'AarvixCMS') }}</span>
        </h1>
        <p class="mt-4 max-w-2xl text-xl text-gray-500 dark:text-gray-400 mx-auto">
            {{ \App\Models\Setting::get('site_description', 'A beautiful, blazing fast content management system powered by Laravel 13 and Tailwind v4. Read our latest thoughts below.') }}
        </p>
    </div>
</section>

<!-- Blog Feed -->
<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid gap-12 md:grid-cols-2">
        @forelse($posts as $post)
            <article class="group relative flex flex-col items-start justify-between bg-white dark:bg-gray-800/50 p-6 sm:p-8 rounded-3xl border border-gray-200 dark:border-gray-800 hover:border-indigo-500/50 dark:hover:border-indigo-400/50 hover:shadow-xl hover:shadow-indigo-500/10 dark:hover:shadow-indigo-400/5 transition-all duration-300">
                <div class="flex items-center gap-x-4 text-xs">
                    <time datetime="{{ $post->published_at->format('Y-m-d') }}" class="text-gray-500 dark:text-gray-400">
                        {{ $post->published_at->format('M j, Y') }}
                    </time>
                    @if($post->category)
                        <span class="relative z-10 rounded-full bg-gray-50 dark:bg-gray-900 px-3 py-1.5 font-medium text-gray-600 dark:text-gray-300 border border-gray-100 dark:border-gray-700">
                            {{ $post->category->name }}
                        </span>
                    @endif
                </div>
                <div class="group relative mt-4">
                    <h3 class="font-heading mt-3 text-2xl font-semibold leading-6 text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                        <a href="{{ route('post.show', $post->slug) }}">
                            <span class="absolute inset-0"></span>
                            {{ $post->title }}
                        </a>
                    </h3>
                    <p class="mt-4 line-clamp-3 text-sm leading-6 text-gray-600 dark:text-gray-400">
                        {{ $post->excerpt ?? Str::limit(strip_tags($post->body), 150) }}
                    </p>
                </div>
                <div class="relative mt-6 flex items-center gap-x-4">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/50 dark:to-purple-900/50 flex items-center justify-center border border-indigo-200 dark:border-indigo-800">
                        <span class="text-indigo-700 dark:text-indigo-300 font-medium text-sm">
                            {{ substr($post->author->name ?? 'A', 0, 1) }}
                        </span>
                    </div>
                    <div class="text-sm leading-6">
                        <p class="font-semibold text-gray-900 dark:text-white">
                            <span class="absolute inset-0"></span>
                            {{ $post->author->name ?? 'Unknown Author' }}
                        </p>
                        <p class="text-gray-600 dark:text-gray-400">Author</p>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-2 text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">No posts have been published yet.</p>
            </div>
        @endforelse
    </div>

    @if($posts->hasPages())
        <div class="mt-16">
            {{ $posts->links() }}
        </div>
    @endif
</section>
@endsection
