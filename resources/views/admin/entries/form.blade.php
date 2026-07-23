@extends('layouts.admin')

@section('header', ($record->exists ? 'Edit' : 'New') . ' ' . $contentType->name)

@section('content')
<form action="{{ $record->exists
        ? route('admin.entries.update', ['type' => $contentType->slug, 'entry' => $record->id])
        : route('admin.entries.store', ['type' => $contentType->slug]) }}"
      method="POST" class="space-y-6">
    @csrf
    @if($record->exists) @method('PUT') @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title', $record->title) }}" required
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-lg font-medium transition-all">
                    @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-400 text-sm">/{{ $contentType->slug }}/</span>
                        <input type="text" name="slug" id="slug" value="{{ old('slug', $record->slug) }}"
                               class="flex-1 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                    </div>
                    @error('slug') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Editor.js body --}}
                <div>
                    <x-admin.form.editorjs
                        name="body"
                        label="Body"
                        :value="$record->body"
                        ai-context="entry"
                        :ai-record-id="$record->exists ? $record->id : null"
                        :ai-content-type-slug="$contentType->slug"
                    />
                </div>
            </div>

            {{-- Custom Fields --}}
            @if($contentType->fieldDefinitions()->isNotEmpty())
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ $contentType->name }} Details</h4>
                    <div class="space-y-4">
                        @foreach($contentType->fieldDefinitions() as $field)
                            @php $value = old("custom_fields.{$field['key']}", $record->getCustomField($field['key'])) @endphp
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $field['label'] }}
                                    @if(!empty($field['required'])) <span class="text-red-500">*</span> @endif
                                </label>

                                @switch($field['type'])
                                    @case('textarea')
                                        <textarea name="custom_fields[{{ $field['key'] }}]" rows="3"
                                                  class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">{{ $value }}</textarea>
                                        @break
                                    @case('select')
                                        <select name="custom_fields[{{ $field['key'] }}]"
                                                class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                                            <option value="">— Select —</option>
                                            @foreach(explode(',', $field['options'] ?? '') as $opt)
                                                <option value="{{ trim($opt) }}" {{ $value == trim($opt) ? 'selected' : '' }}>{{ trim($opt) }}</option>
                                            @endforeach
                                        </select>
                                        @break
                                    @case('checkbox')
                                        <input type="hidden" name="custom_fields[{{ $field['key'] }}]" value="0">
                                        <input type="checkbox" name="custom_fields[{{ $field['key'] }}]" value="1"
                                               {{ $value ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        @break
                                    @default
                                        <input type="{{ $field['type'] }}" name="custom_fields[{{ $field['key'] }}]"
                                               value="{{ $value }}"
                                               class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                                @endswitch

                                @error("custom_fields.{$field['key']}") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar Meta --}}
        <div class="space-y-5">
            {{-- Publish Box --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5 space-y-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Publish</h4>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="draft" {{ old('status', $record->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $record->status) === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="archived" {{ old('status', $record->status) === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Published At</label>
                    <input type="datetime-local" name="published_at"
                           value="{{ old('published_at', $record->published_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <button type="submit"
                        class="w-full py-2 px-4 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors">
                    {{ $record->exists ? 'Save Changes' : 'Create Entry' }}
                </button>
                <a href="{{ route('admin.entries.index', ['type' => $contentType->slug]) }}"
                   class="block text-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Cancel</a>
            </div>

            {{-- Category --}}
            @if($categories->isNotEmpty())
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Category</h4>
                    <select name="category_id" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                        <option value="">— None —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $record->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Tags --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Tags</h4>
                <div class="space-y-1.5 max-h-40 overflow-y-auto">
                    @foreach($tags as $tag)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                   {{ in_array($tag->id, old('tags', $record->tags->pluck('id')->toArray())) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            {{ $tag->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- SEO --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5 space-y-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">SEO</h4>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $record->meta_title) }}"
                           class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Meta Description</label>
                    <textarea name="meta_description" rows="2"
                              class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('meta_description', $record->meta_description) }}</textarea>
                </div>
                @include('admin.partials.ai-seo-panel', [
                    'aiContext' => 'entry',
                    'aiRecordId' => $record->exists ? $record->id : null,
                    'aiContentTypeSlug' => $contentType->slug,
                    'aiTitleField' => 'title',
                    'aiBodyField' => 'body',
                    'aiSlugField' => 'slug',
                    'aiMetaTitleField' => 'meta_title',
                    'aiMetaDescriptionField' => 'meta_description',
                ])
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('title').addEventListener('input', function () {
    const slugEl = document.getElementById('slug');
    if (!slugEl.dataset.manual) {
        slugEl.value = this.value.toLowerCase().replace(/[^a-z0-9\s\-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
    }
});
document.getElementById('slug').addEventListener('input', function () {
    this.dataset.manual = 'true';
});
</script>
@endsection
