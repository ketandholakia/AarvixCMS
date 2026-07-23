@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description)

@section('content')
<article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <header class="mb-10 border-b border-gray-200 pb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-indigo-600">
            Page
        </p>
        <h1 class="mt-3 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
            {{ $page->title }}
        </h1>
    </header>

    <div class="prose max-w-none prose-gray">
        {!! app(\App\Services\BlockParser::class)->parse($page->body) !!}
    </div>
</article>
@endsection
