@extends('layouts.admin')

@section('header', 'Media Details')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Media item</p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $media->filename }}</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ $media->human_size }} · {{ $media->mime_type }}
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.media.index') }}" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                Back to media
            </a>
            @if($media->isImage())
                <form action="{{ route('admin.media.analyze', $media) }}" method="POST">
                    @csrf
                    <input type="hidden" name="analysis_type" value="vision">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">
                        Analyze with AI
                    </button>
                </form>
                <form action="{{ route('admin.media.analyze', $media) }}" method="POST">
                    @csrf
                    <input type="hidden" name="analysis_type" value="screenshot">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-700 text-white hover:bg-slate-800">
                        Analyze screenshot
                    </button>
                </form>
            @endif
            <a href="{{ $media->url }}" target="_blank" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">
                View file
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="bg-gray-50 dark:bg-gray-800/60 p-4 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Preview</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Stored media and current alt text</p>
                    </div>
                    @if($media->aiImageAsset)
                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                            AI generated
                        </span>
                    @endif
                </div>
            </div>

            <div class="p-6">
                @if($media->isImage())
                    <img src="{{ $media->url }}" alt="{{ $media->alt_text }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-800 object-contain bg-gray-50 dark:bg-gray-950">
                @else
                    <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 p-16 text-center text-gray-500 dark:text-gray-400">
                        This media item is not an image.
                    </div>
                @endif

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Alt text</p>
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $media->alt_text ?: 'No alt text set.' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Caption</p>
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $media->caption ?: 'No caption set.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">File Info</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Disk</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $media->disk }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Path</dt>
                        <dd class="font-mono text-xs text-gray-700 dark:text-gray-300 break-all">{{ $media->path }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Filename</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $media->filename }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Uploaded</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ optional($media->created_at)->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Provenance</h3>

                @if($media->aiImageAsset)
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Provider</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $media->aiImageAsset->provider }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Model</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $media->aiImageAsset->model }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Operation</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $media->aiImageAsset->operation }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Moderation</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $media->aiImageAsset->moderation_status }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Resolution</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $media->aiImageAsset->resolution ?: 'n/a' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Seed</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $media->aiImageAsset->seed ?? 'n/a' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Retains until</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ optional($media->aiImageAsset->retention_expires_at)->format('Y-m-d H:i') ?? 'n/a' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-4">
                        @if($media->aiImageAsset->alt_text)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">AI alt text</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $media->aiImageAsset->alt_text }}</p>
                            </div>
                        @endif
                        @if($media->aiImageAsset->caption)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">AI caption</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $media->aiImageAsset->caption }}</p>
                            </div>
                        @endif
                        @if(! empty($media->aiImageAsset->tags))
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tags</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ implode(', ', $media->aiImageAsset->tags) }}</p>
                            </div>
                        @endif
                        @if($media->aiImageAsset->ocr_text)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">OCR text</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $media->aiImageAsset->ocr_text }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt hash</p>
                            <p class="mt-1 font-mono text-xs text-gray-700 dark:text-gray-300 break-all">{{ $media->aiImageAsset->prompt_hash }}</p>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        This media item does not have AI provenance attached.
                    </p>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Vision Analysis</h3>

                @if($media->aiVisionAnalysis)
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $media->aiVisionAnalysis->analysis_type }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Provider</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $media->aiVisionAnalysis->provider }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Model</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $media->aiVisionAnalysis->model }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Analyzed</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ optional($media->aiVisionAnalysis->analyzed_at)->format('Y-m-d H:i') ?? 'n/a' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 space-y-4">
                        @if($media->aiVisionAnalysis->summary)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Summary</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $media->aiVisionAnalysis->summary }}</p>
                            </div>
                        @endif
                        @if($media->aiVisionAnalysis->alt_text)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Suggested alt text</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $media->aiVisionAnalysis->alt_text }}</p>
                            </div>
                        @endif
                        @if($media->aiVisionAnalysis->caption)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Suggested caption</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $media->aiVisionAnalysis->caption }}</p>
                            </div>
                        @endif
                        @if(! empty($media->aiVisionAnalysis->tags))
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tags</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ implode(', ', $media->aiVisionAnalysis->tags) }}</p>
                            </div>
                        @endif
                        @if($media->aiVisionAnalysis->ocr_text)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">OCR text</p>
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $media->aiVisionAnalysis->ocr_text }}</p>
                            </div>
                        @endif
                        @if(! empty($media->aiVisionAnalysis->structured_data))
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Structured data</p>
                                <pre class="mt-1 overflow-x-auto rounded-lg bg-gray-950 p-3 text-[11px] leading-5 text-gray-100">{{ json_encode($media->aiVisionAnalysis->structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt hash</p>
                            <p class="mt-1 font-mono text-xs text-gray-700 dark:text-gray-300 break-all">{{ $media->aiVisionAnalysis->prompt_hash }}</p>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        No vision analysis has been queued for this media item yet.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
