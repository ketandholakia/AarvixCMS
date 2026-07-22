@extends('layouts.app')

@section('title', $entry->meta_title ?: $entry->title)
@section('meta_description', $entry->meta_description)

@section('content')
<article class="bg-white dark:bg-gray-900 transition-colors">
    {{-- Header --}}
    <div class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 mb-3">
                <a href="/{{ $contentType->slug }}" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wide hover:underline">
                    {{ $contentType->name }}
                </a>
                @if($entry->category)
                    <span class="text-gray-400">·</span>
                    <span class="text-xs text-gray-500">{{ $entry->category->name }}</span>
                @endif
            </div>
            <h1 class="font-heading text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white tracking-tight leading-tight">
                {{ $entry->title }}
            </h1>
            <div class="mt-4 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                @if($entry->author)
                    <span>By {{ $entry->author->name }}</span>
                    <span>·</span>
                @endif
                @if($entry->published_at)
                    <time datetime="{{ $entry->published_at->toIso8601String() }}">
                        {{ $entry->published_at->format('F j, Y') }}
                    </time>
                @endif
            </div>
        </div>
    </div>

    {{-- Featured Image --}}
    @if($entry->featuredImage)
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 mb-8">
            <img src="{{ Storage::url($entry->featuredImage->path) }}"
                 alt="{{ $entry->featuredImage->alt_text ?: $entry->title }}"
                 class="w-full rounded-2xl shadow-lg object-cover aspect-video">
        </div>
    @endif

    {{-- Main body (Editor.js blocks) --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="prose dark:prose-invert max-w-none">
            {!! app(\App\Services\BlockParser::class)->parse($entry->body) !!}
        </div>

        {{-- Custom Fields --}}
        @if($contentType->fieldDefinitions()->isNotEmpty())
            <div class="mt-10 pt-8 border-t border-gray-100 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Details</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($contentType->fieldDefinitions() as $field)
                        @php $value = $entry->getCustomField($field['key']) @endphp
                        @if($value !== null && $value !== '')
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $field['label'] }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    @if($field['type'] === 'checkbox')
                                        {{ $value ? 'Yes' : 'No' }}
                                    @elseif($field['type'] === 'url')
                                        <a href="{{ $value }}" class="text-indigo-600 dark:text-indigo-400 hover:underline" target="_blank" rel="noopener">{{ $value }}</a>
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        @endif

        {{-- Tags --}}
        @if($entry->tags->isNotEmpty())
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach($entry->tags as $tag)
                    <a href="{{ route('tag.show', $tag->slug) }}"
                       class="px-3 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</article>
@endsection
