@extends('layouts.admin')

@section('header', $record->exists ? 'Edit Page' : 'Create Page')

@section('content')
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Page Content</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Design your static page.</p>
        </div>
        @if($record->exists)
            <a href="{{ route('page.show', $record->slug) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline" target="_blank">View on Site &nearr;</a>
        @endif
    </div>

    <form action="{{ $record->exists ? route('admin.pages.update', $record->id) : route('admin.pages.store') }}" method="POST">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif

        <div class="flex flex-col lg:flex-row">
            <div class="flex-1 p-6 space-y-6 lg:border-r border-gray-200 dark:border-gray-800" x-data="{ activeTab: 'en' }">
                
                <!-- Language Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-800">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        @foreach(['en' => 'English', 'hi' => 'Hindi (हिन्दी)', 'gu' => 'Gujarati (ગુજરાતી)'] as $loc => $label)
                            <button type="button" 
                                @click="activeTab = '{{ $loc }}'"
                                :class="activeTab === '{{ $loc }}' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                                {{ $label }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                @foreach(['en', 'hi', 'gu'] as $loc)
                    @php
                        // For 'en', we bind directly to the model. For others, we get from the translations array or relationship.
                        $trans = $loc === 'en' ? $record : ($record->translations->where('locale', $loc)->first() ?? new \App\Models\PageTranslation());
                        $prefix = $loc === 'en' ? '' : "translations[{$loc}][";
                        $suffix = $loc === 'en' ? '' : "]";
                    @endphp
                    <div x-show="activeTab === '{{ $loc }}'" class="space-y-6" style="display: none;">
                        <x-admin.form.input 
                            name="{{ $prefix }}title{{ $suffix }}" 
                            label="Page Title ({{ strtoupper($loc) }})" 
                            :value="$trans->title" 
                            :required="$loc === 'en' ? 'true' : 'false'" 
                            class="text-lg font-medium"
                        />

                        @if($loc === 'en')
                        <x-admin.form.input 
                            name="slug" 
                            label="Slug (URL)" 
                            :value="$record->slug" 
                            help="Optional. Leave blank to auto-generate from title." 
                        />
                        @endif
                        
                        <x-admin.form.editorjs 
                            name="{{ $prefix }}body{{ $suffix }}" 
                            label="Page Body ({{ strtoupper($loc) }})" 
                            :value="$trans->body" 
                            @if($loc === 'en')
                                ai-context="page"
                                :ai-record-id="$record->exists ? $record->id : null"
                            @endif
                        />
                    </div>
                @endforeach
            </div>

            <!-- Sidebar Sidebar -->
            <div class="w-full lg:w-80 p-6 space-y-6 bg-gray-50/30 dark:bg-gray-900/30">
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Publishing</h4>
                    
                    <x-admin.form.select 
                        name="status" 
                        label="Status" 
                        :value="$record->status ?? 'draft'"
                        :options="['draft' => 'Draft', 'published' => 'Published']"
                    />

                    <x-admin.form.select 
                        name="template" 
                        label="Page Template" 
                        :value="$record->template ?? 'default'" 
                        :options="['default' => 'Default (Centered)', 'full-width' => 'Full Width', 'sidebar' => 'With Sidebar', 'landing' => 'Landing (No Header)']"
                    />

                    <x-admin.form.input 
                        name="published_at" 
                        label="Publish Date" 
                        type="datetime-local"
                        :value="optional($record->published_at)->format('Y-m-d\TH:i')"
                    />

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_premium" value="0">
                            <input type="checkbox" name="is_premium" value="1" {{ $record->is_premium ? 'checked' : '' }} class="w-5 h-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <div>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Premium Page</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Only accessible to paying subscribers</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4" x-data="{ seoTab: 'en' }">
                    <h4 class="font-medium text-gray-900 dark:text-white flex justify-between items-center">
                        SEO Meta
                        <div class="flex gap-2">
                            @foreach(['en', 'hi', 'gu'] as $loc)
                                <button type="button" @click="seoTab = '{{ $loc }}'" :class="seoTab === '{{ $loc }}' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'" class="px-2 py-1 text-xs rounded font-medium transition-colors">{{ strtoupper($loc) }}</button>
                            @endforeach
                        </div>
                    </h4>
                    
                    @foreach(['en', 'hi', 'gu'] as $loc)
                        @php
                            $trans = $loc === 'en' ? $record : ($record->translations->where('locale', $loc)->first() ?? new \App\Models\PageTranslation());
                            $prefix = $loc === 'en' ? '' : "translations[{$loc}][";
                            $suffix = $loc === 'en' ? '' : "]";
                        @endphp
                        <div x-show="seoTab === '{{ $loc }}'" style="display: none;" class="space-y-4">
                            <x-admin.form.input 
                                name="{{ $prefix }}meta_title{{ $suffix }}" 
                                label="Meta Title ({{ strtoupper($loc) }})" 
                                :value="$trans->meta_title" 
                                help="Overrides the default title tag." 
                            />

                            <x-admin.form.textarea 
                                name="{{ $prefix }}meta_description{{ $suffix }}" 
                                label="Meta Description ({{ strtoupper($loc) }})" 
                                :value="$trans->meta_description" 
                                rows="3"
                                help="Appears in search engine results." 
                            />

                            @include('admin.partials.ai-seo-panel', [
                                'aiContext' => 'page',
                                'aiRecordId' => $record->exists ? $record->id : null,
                                'aiTitleField' => $loc === 'en' ? 'title' : "translations[{$loc}][title]",
                                'aiBodyField' => $loc === 'en' ? 'body' : "translations[{$loc}][body]",
                                'aiSlugField' => $loc === 'en' ? 'slug' : null,
                                'aiMetaTitleField' => $prefix . 'meta_title' . $suffix,
                                'aiMetaDescriptionField' => $prefix . 'meta_description' . $suffix,
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
            <a href="{{ route('admin.pages.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Update Page' : 'Save Page' }}
            </button>
        </div>
    </form>
</div>
@endsection
