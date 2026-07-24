@extends('layouts.admin')

@section('header', 'Media Library')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Media Library</h2>
        <label for="upload-trigger" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-medium cursor-pointer">
            Upload Files
        </label>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    {{-- Hidden upload form triggered by label --}}
    <form id="upload-form" action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
        <input id="upload-trigger" type="file" name="file" accept="image/*" class="sr-only"
               onchange="this.closest('form').submit()">
    </form>

    {{-- Search --}}
    <form method="GET" action="{{ route('admin.media.index') }}" class="flex gap-3">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by filename or alt text..."
            class="flex-1 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
        <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-medium transition-colors">
            Search
        </button>
        @if(request('search'))
            <a href="{{ route('admin.media.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">Clear</a>
        @endif
    </form>

    {{-- Media Grid --}}
    @if($records->isEmpty())
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-16 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 mb-2">No media files yet.</p>
            <label for="upload-trigger" class="text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer text-sm font-medium">Upload your first image</label>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($records as $media)
                <div class="group relative bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    {{-- Thumbnail --}}
                    <div class="aspect-square bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden">
                        @if($media->isImage())
                            <img
                                src="{{ $media->url }}"
                                alt="{{ $media->alt_text }}"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                        @else
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="p-2">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate" title="{{ $media->filename }}">
                            {{ $media->filename }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $media->human_size }}</p>
                        @if($media->aiImageAsset)
                            <div class="mt-2 space-y-1">
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                                        AI image
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        {{ $media->aiImageAsset->operation }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                                        {{ $media->aiImageAsset->moderation_status }}
                                    </span>
                                </div>
                                @if(! empty($media->aiImageAsset->tags))
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate" title="{{ implode(', ', $media->aiImageAsset->tags) }}">
                                        Tags: {{ implode(', ', $media->aiImageAsset->tags) }}
                                    </p>
                                @endif
                                @if(! empty($media->aiImageAsset->ocr_text))
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-2" title="{{ $media->aiImageAsset->ocr_text }}">
                                        OCR: {{ $media->aiImageAsset->ocr_text }}
                                    </p>
                                @endif
                                @if($media->aiImageAsset->request)
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate" title="{{ $media->aiImageAsset->request->request_uuid }}">
                                        Request: {{ $media->aiImageAsset->request->request_uuid }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Delete overlay --}}
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <a href="{{ $media->url }}" target="_blank"
                           class="p-1.5 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors" title="View">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                        <a href="{{ route('admin.media.show', $media) }}"
                           class="p-1.5 bg-indigo-500/80 text-white rounded-lg hover:bg-indigo-600 transition-colors" title="Details">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                        <form action="{{ route('admin.media.destroy', $media->id) }}" method="POST"
                              onsubmit="return confirm('Delete this file permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 bg-red-500/80 text-white rounded-lg hover:bg-red-600 transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div>{{ $records->links() }}</div>
    @endif
</div>
@endsection
