@extends('layouts.admin')

@section('header', $record->exists ? 'Edit Content Type' : 'New Content Type')

@section('content')
<div class="max-w-2xl">
    <form action="{{ $record->exists ? route('admin.content-types.update', $record->id) : route('admin.content-types.store') }}"
          method="POST" class="space-y-6">
        @csrf
        @if($record->exists) @method('PUT') @endif

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $record->name) }}" required
                       class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Slug / URL Prefix <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-400 font-normal ml-1">(lowercase letters, numbers and hyphens only)</span>
                </label>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400 text-sm">/</span>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $record->slug) }}" required
                           pattern="[a-z0-9\-]+" placeholder="portfolio"
                           {{ $record->is_system ? 'disabled' : '' }}
                           class="flex-1 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    <span class="text-gray-400 text-sm">/{slug}</span>
                </div>
                @error('slug') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Context <span class="text-red-500">*</span></label>
                <select name="context" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="post" {{ old('context', $record->context ?? 'post') === 'post' ? 'selected' : '' }}>Post (blog-like, listed in feeds)</option>
                    <option value="page" {{ old('context', $record->context) === 'page' ? 'selected' : '' }}>Page (standalone, not in feeds)</option>
                </select>
                @error('context') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icon</label>
                <input type="text" name="icon" value="{{ old('icon', $record->icon) }}" placeholder="briefcase"
                       class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                <p class="mt-1 text-xs text-gray-400">Heroicon name for the admin sidebar link.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea name="description" rows="2"
                          class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">{{ old('description', $record->description) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', $record->is_active ?? true) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active (show in admin sidebar and frontend)</label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Save Changes' : 'Create Content Type' }}
            </button>
            <a href="{{ route('admin.content-types.index') }}"
               class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

@if(!$record->exists)
<script>
    document.querySelector('[name="name"]').addEventListener('input', function () {
        document.getElementById('slug').value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s\-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    });
</script>
@endif
@endsection
