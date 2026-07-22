@props(['name', 'label' => '', 'value' => '', 'required' => false, 'help' => ''])

<div class="space-y-1.5" x-data="editorJsComponent('{{ $name }}', `{{ old($name, $value) }}`)">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif
    
    <!-- Hidden textarea that the form actually submits -->
    <textarea name="{{ $name }}" id="{{ $name }}" x-ref="textarea" class="hidden">{{ old($name, $value) }}</textarea>
    
    <!-- Editor.js Container -->
    <div 
        id="editorjs_{{ Str::slug($name, '_') }}" 
        class="block w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 prose prose-indigo dark:prose-invert max-w-none p-4 min-h-[400px]"
        x-ref="editor"
    ></div>
    
    @error($name)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    @if($help && !$errors->has($name))
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif
</div>

@push('scripts')
@once
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/delimiter@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@latest"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('editorJsComponent', (name, initialData) => ({
            editor: null,
            init() {
                let parsedData = {};
                try {
                    parsedData = initialData ? JSON.parse(initialData) : {};
                } catch(e) {
                    // It might be raw HTML from before. Let's wrap it in a raw block or paragraph
                    parsedData = {
                        blocks: [
                            {
                                type: "paragraph",
                                data: { text: initialData }
                            }
                        ]
                    };
                }

                this.editor = new EditorJS({
                    holder: this.$refs.editor.id,
                    data: parsedData,
                    tools: {
                        header: Header,
                        list: List,
                        image: {
                            class: ImageTool,
                            config: {
                                endpoints: {
                                    byFile: '/admin/media', // Uses MediaController@store
                                }
                            }
                        },
                        quote: Quote,
                        delimiter: Delimiter,
                        code: CodeTool,
                    },
                    onChange: (api, event) => {
                        api.saver.save().then((outputData) => {
                            this.$refs.textarea.value = JSON.stringify(outputData);
                        });
                    }
                });
                
                // Also update on form submit just to be sure
                this.$refs.textarea.closest('form').addEventListener('submit', (e) => {
                    this.editor.save().then((outputData) => {
                        this.$refs.textarea.value = JSON.stringify(outputData);
                    });
                });
            }
        }));
    });
</script>
<style>
    /* Basic overrides for editorjs dark mode integration */
    .dark .ce-block__content, 
    .dark .ce-toolbar__content {
        color: #e5e7eb;
    }
    .dark .ce-toolbar__plus,
    .dark .ce-toolbar__settings-btn {
        color: #9ca3af;
    }
    .dark .ce-popover {
        background: #1f2937;
        border-color: #374151;
    }
    .dark .ce-popover__item:hover {
        background: #374151;
    }
    .dark .cdx-input {
        background: #374151;
        border-color: #4b5563;
        color: #e5e7eb;
    }
</style>
@endonce
@endpush
