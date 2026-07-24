@extends('layouts.admin')

@section('header', $record->exists ? 'Edit Post' : 'Create Post')

@section('content')
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Post Content</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Write your article here.</p>
        </div>
        <div class="flex items-center gap-4">
            @if($record->exists)
                <a href="{{ route('admin.revisions.index', ['type' => 'post', 'id' => $record->id]) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Revision History</a>
                <a href="{{ route('post.show', $record->slug) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline" target="_blank">View on Site &nearr;</a>
            @endif
        </div>
    </div>

    <form action="{{ $record->exists ? route('admin.posts.update', $record->id) : route('admin.posts.store') }}" method="POST">
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
                        $trans = $loc === 'en' ? $record : ($record->translations->where('locale', $loc)->first() ?? new \App\Models\PostTranslation());
                        $prefix = $loc === 'en' ? '' : "translations[{$loc}][";
                        $suffix = $loc === 'en' ? '' : "]";
                    @endphp
                    <div x-show="activeTab === '{{ $loc }}'" class="space-y-6" style="display: none;">
                        <x-admin.form.input 
                            name="{{ $prefix }}title{{ $suffix }}" 
                            label="Post Title ({{ strtoupper($loc) }})" 
                            :value="$trans->title" 
                            :required="$loc === 'en'" 
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
                        
                        <x-admin.form.textarea 
                            name="{{ $prefix }}excerpt{{ $suffix }}" 
                            label="Excerpt ({{ strtoupper($loc) }})" 
                            :value="$trans->excerpt" 
                            rows="2"
                            help="A brief summary for blog listings and SEO." 
                        />

                        @if($loc === 'en')
                            <x-admin.form.editorjs
                                name="{{ $prefix }}body{{ $suffix }}"
                                label="Body Content ({{ strtoupper($loc) }})"
                                :value="$trans->body"
                                ai-context="post"
                                :ai-record-id="$record->exists ? $record->id : null"
                            />
                        @else
                            <x-admin.form.editorjs
                                name="{{ $prefix }}body{{ $suffix }}"
                                label="Body Content ({{ strtoupper($loc) }})"
                                :value="$trans->body"
                            />
                        @endif
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
                        :options="['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled']"
                    />

                    <x-admin.form.input 
                        name="published_at" 
                        label="Publish Date" 
                        type="datetime-local"
                        :value="$record->published_at ? $record->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')" 
                    />

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_premium" value="0">
                            <input type="checkbox" name="is_premium" value="1" {{ $record->is_premium ? 'checked' : '' }} class="w-5 h-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <div>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Premium Content</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Only accessible to paying subscribers</span>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4" x-data="{ openMedia: false }">
                    <h4 class="font-medium text-gray-900 dark:text-white">Featured Image</h4>
                    
                    <input type="hidden" name="featured_image" id="featured_image_input" value="{{ $record->featured_image }}">
                    
                    <div class="mt-2 text-center">
                        <template x-if="document.getElementById('featured_image_input') && document.getElementById('featured_image_input').value">
                            <div class="relative group">
                                <img :src="document.getElementById('featured_image_input').value" class="w-full h-auto rounded-lg border border-gray-200">
                                <button type="button" @click="document.getElementById('featured_image_input').value = ''" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="!document.getElementById('featured_image_input') || !document.getElementById('featured_image_input').value">
                            <button type="button" @click="alert('Media Modal would open here to pick an image. For now, type URL directly.')" class="w-full py-8 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg text-gray-500 hover:text-indigo-500 hover:border-indigo-500 transition-colors">
                                Select Image
                            </button>
                        </template>
                        <div class="mt-2">
                            <x-admin.form.input name="featured_image" label="Or Image URL" :value="$record->featured_image" help="Paste image URL here" />
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Organization</h4>
                    
                    @php
                        $categories = \App\Models\Category::orderBy('name')->pluck('name', 'id')->toArray();
                    @endphp

                    <x-admin.form.select 
                        name="category_id" 
                        label="Category" 
                        :value="$record->category_id"
                        :options="$categories"
                    />
                </div>

                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Tags</h4>
                    @php
                        $allTags = \App\Models\Tag::orderBy('name')->pluck('name', 'id')->toArray();
                        $selectedTags = $record->exists ? $record->tags->pluck('id')->toArray() : [];
                    @endphp
                    <div class="space-y-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Select Tags</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($allTags as $id => $name)
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="tags[]" value="{{ $id }}"
                                           {{ in_array($id, $selectedTags) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @if(empty($allTags))
                            <p class="text-xs text-gray-400">No tags yet. <a href="{{ route('admin.tags.create') }}" class="text-indigo-500 hover:underline">Create one</a>.</p>
                        @endif
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
                            $trans = $loc === 'en' ? $record : ($record->translations->where('locale', $loc)->first() ?? new \App\Models\PostTranslation());
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
                                'aiContext' => 'post',
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
            <a href="{{ route('admin.posts.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                {{ $record->exists ? 'Update Post' : 'Save Post' }}
            </button>
        </div>
    </form>
</div>
@endsection
