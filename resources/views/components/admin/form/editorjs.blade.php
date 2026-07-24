@props(['name', 'label' => '', 'value' => '', 'required' => false, 'help' => '', 'aiContext' => null, 'aiRecordId' => null, 'aiContentTypeSlug' => null, 'locale' => 'en', 'placeholder' => null])

@php
    $editorPlaceholder = $placeholder ?: match ($locale) {
        'hi' => 'यहां सामग्री लिखें। नए ब्लॉक जोड़ने के लिए "/" या + बटन का उपयोग करें।',
        'gu' => 'અહીં સામગ્રી લખો. નવા બ્લોક્સ ઉમેરવા માટે "/" અથવા + બટનનો ઉપયોગ કરો.',
        default => 'Write the page content here. Use "/" or the + button to add blocks.',
    };
@endphp

<div
    class="space-y-3"
    data-editorjs-root
    data-editorjs-name="{{ $name }}"
    data-editorjs-initial='@json(old($name, $value))'
    data-editorjs-placeholder="{{ $editorPlaceholder }}"
>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    @if($aiContext)
        @include('admin.partials.ai-writer-panel', [
            'aiContext' => $aiContext,
            'aiRecordId' => $aiRecordId,
            'aiContentTypeSlug' => $aiContentTypeSlug,
        ])
    @endif
    
    <!-- Fallback textarea remains usable if Editor.js fails to boot -->
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="16"
        class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
    >{{ old($name, $value) }}</textarea>
    
    <!-- Editor.js Container -->
    <div 
        id="editorjs_{{ Str::slug($name, '_') }}" 
        class="hidden w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-900 dark:border-gray-700 prose prose-indigo dark:prose-invert max-w-none p-4 min-h-[400px]"
        data-editorjs-holder
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
<script src="https://cdn.jsdelivr.net/npm/@editorjs/marker@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/underline@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/table@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/embed@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/warning@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/raw@latest"></script>

<script>
    (() => {
        const initEditorJsRoots = () => {
            const roots = document.querySelectorAll('[data-editorjs-root]');

            if (! roots.length || typeof window.EditorJS === 'undefined') {
                return;
            }

            window.AarvixEditorJs = window.AarvixEditorJs || {};
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const uploadEndpoint = '{{ route('admin.upload.image') }}';

            roots.forEach((root) => {
                if (root.dataset.editorjsInitialized === 'true') {
                    return;
                }

                root.dataset.editorjsInitialized = 'true';

                const name = root.dataset.editorjsName || '';
                const textarea = root.querySelector('textarea');
                const holder = root.querySelector('[data-editorjs-holder]');
                const initialData = textarea?.value || root.dataset.editorjsInitial || '';
                const placeholder = root.dataset.editorjsPlaceholder || 'Write the page content here.';

                if (! textarea || ! holder || ! name) {
                    return;
                }

                const emptyDocument = {
                    blocks: [
                        {
                            type: 'paragraph',
                            data: { text: '' },
                        },
                    ],
                };

                let parsedData = {};
                try {
                    parsedData = initialData ? JSON.parse(initialData) : emptyDocument;
                } catch(e) {
                    // It might be raw HTML from before. Let's wrap it in a raw block or paragraph
                    parsedData = initialData
                        ? {
                            blocks: [
                                {
                                    type: 'paragraph',
                                    data: { text: initialData },
                                },
                            ],
                        }
                        : emptyDocument;
                }

                if (! Array.isArray(parsedData.blocks) || parsedData.blocks.length === 0) {
                    parsedData = emptyDocument;
                }

                const tools = {
                    header: window.Header ? {
                        class: window.Header,
                        inlineToolbar: ['link', 'marker', 'bold', 'italic'],
                        config: {
                            levels: [2, 3, 4],
                            defaultLevel: 2,
                        },
                    } : undefined,
                    list: window.EditorjsList || window.List ? {
                        class: window.EditorjsList || window.List,
                        inlineToolbar: true,
                    } : undefined,
                    image: window.ImageTool ? {
                        class: window.ImageTool,
                        config: {
                            captionPlaceholder: 'Describe the image',
                            uploader: {
                                async uploadByFile(file) {
                                    const formData = new FormData();
                                    formData.append('file', file);

                                    const response = await fetch(uploadEndpoint, {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': csrfToken,
                                            'Accept': 'application/json',
                                        },
                                        body: formData,
                                        credentials: 'same-origin',
                                    });

                                    const payload = await response.json();

                                    if (! response.ok) {
                                        throw new Error(payload.message || 'Image upload failed.');
                                    }

                                    return payload;
                                },
                                async uploadByUrl(url) {
                                    const response = await fetch(uploadEndpoint, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken,
                                            'Accept': 'application/json',
                                        },
                                        credentials: 'same-origin',
                                        body: JSON.stringify({ url }),
                                    });

                                    const payload = await response.json();

                                    if (! response.ok) {
                                        throw new Error(payload.message || 'Image import failed.');
                                    }

                                    return payload;
                                },
                            },
                        }
                    } : undefined,
                    quote: window.Quote ? {
                        class: window.Quote,
                        inlineToolbar: ['link', 'marker', 'bold', 'italic'],
                        config: {
                            quotePlaceholder: 'Add a quote',
                            captionPlaceholder: 'Quote source',
                        },
                    } : undefined,
                    delimiter: window.Delimiter ? {
                        class: window.Delimiter,
                    } : undefined,
                    code: window.CodeTool ? {
                        class: window.CodeTool,
                    } : undefined,
                    marker: window.Marker ? {
                        class: window.Marker,
                    } : undefined,
                    underline: window.Underline ? {
                        class: window.Underline,
                    } : undefined,
                    checklist: window.EditorjsList || window.List ? {
                        class: window.EditorjsList || window.List,
                        inlineToolbar: true,
                        config: {
                            defaultStyle: 'checklist',
                        },
                    } : undefined,
                    table: window.Table ? {
                        class: window.Table,
                        inlineToolbar: true,
                        config: {
                            rows: 2,
                            cols: 3,
                        },
                    } : undefined,
                    embed: window.Embed ? {
                        class: window.Embed,
                        config: {
                            services: {
                                youtube: true,
                                vimeo: true,
                                instagram: true,
                                x: true,
                            },
                        },
                    } : undefined,
                    warning: window.Warning ? {
                        class: window.Warning,
                        inlineToolbar: true,
                        config: {
                            titlePlaceholder: 'Callout title',
                            messagePlaceholder: 'Add the key message',
                        },
                    } : undefined,
                    raw: window.RawTool ? {
                        class: window.RawTool,
                    } : undefined,
                };

                Object.keys(tools).forEach((toolName) => {
                    if (! tools[toolName]) {
                        delete tools[toolName];
                    }
                });

                let documentData = parsedData;
                let submitting = false;

                const editor = new EditorJS({
                    holder: holder.id,
                    data: parsedData,
                    autofocus: false,
                    defaultBlock: 'paragraph',
                    inlineToolbar: ['link', 'marker', 'bold', 'italic', 'underline'],
                    placeholder,
                    tools,
                    onChange: (api) => {
                        api.saver.save().then((outputData) => {
                            documentData = outputData;
                            textarea.value = JSON.stringify(outputData);
                        });
                    },
                    onReady: () => {
                        holder.dataset.editorReady = 'true';
                        holder.classList.remove('hidden');
                        textarea.classList.add('hidden');
                    },
                });

                editor.isReady.catch((error) => {
                    console.error('Editor.js failed to initialize', error);
                    holder.classList.add('hidden');
                    textarea.classList.remove('hidden');
                });

                window.AarvixEditorJs[name] = {
                    applyPreview(blocks, mode) {
                        const snapshot = JSON.parse(JSON.stringify(documentData || {}));
                        const nextBlocks = mode === 'insert'
                            ? [...(documentData?.blocks || []), ...blocks]
                            : blocks;

                        const nextData = {
                            ...(documentData || {}),
                            blocks: nextBlocks,
                        };

                        documentData = nextData;
                        textarea.value = JSON.stringify(nextData);

                        return editor.render(nextData).catch((error) => {
                            documentData = snapshot;
                            textarea.value = JSON.stringify(snapshot);

                            return editor.render(snapshot).then(() => {
                                throw error;
                            });
                        });
                    }
                };

                textarea.closest('form')?.addEventListener('submit', (e) => {
                    if (submitting) {
                        return;
                    }

                    e.preventDefault();
                    submitting = true;

                    editor.save()
                        .then((outputData) => {
                            documentData = outputData;
                            textarea.value = JSON.stringify(outputData);
                            e.target.submit();
                        })
                        .catch((error) => {
                            submitting = false;
                            throw error;
                        });
                });
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initEditorJsRoots, { once: true });
        } else {
            initEditorJsRoots();
        }
    })();
</script>
<style>
    [data-editorjs-holder] .ce-block__content,
    [data-editorjs-holder] .ce-toolbar__content {
        max-width: 100%;
    }

    [data-editorjs-holder] .codex-editor {
        padding-bottom: 1rem;
    }

    [data-editorjs-holder] .ce-paragraph {
        line-height: 1.8;
        color: #111827;
    }

    [data-editorjs-holder] .ce-header {
        color: #111827;
        font-weight: 700;
    }

    [data-editorjs-holder] .ce-toolbar__plus,
    [data-editorjs-holder] .ce-toolbar__settings-btn {
        color: #4f46e5;
    }

    [data-editorjs-holder] .ce-inline-toolbar,
    [data-editorjs-holder] .ce-conversion-toolbar,
    [data-editorjs-holder] .ce-popover {
        border-radius: 0.9rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.12);
    }

    [data-editorjs-holder] .ce-inline-toolbar {
        background: #ffffff;
    }

    [data-editorjs-holder] .ce-conversion-toolbar,
    [data-editorjs-holder] .ce-popover {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
    }

    [data-editorjs-holder] .ce-popover__item:hover,
    [data-editorjs-holder] .ce-conversion-tool:hover {
        background: #eef2ff;
    }

    [data-editorjs-holder] .cdx-input {
        border-radius: 0.75rem;
    }

    /* Basic overrides for editorjs dark mode integration */
    .dark .ce-block__content, 
    .dark .ce-toolbar__content {
        color: #e5e7eb;
    }
    .dark [data-editorjs-holder] .ce-paragraph,
    .dark [data-editorjs-holder] .ce-header {
        color: #e5e7eb;
    }
    .dark .ce-toolbar__plus,
    .dark .ce-toolbar__settings-btn {
        color: #a5b4fc;
    }
    .dark .ce-inline-toolbar,
    .dark .ce-conversion-toolbar,
    .dark .ce-popover {
        background: rgba(17, 24, 39, 0.96);
        border-color: #374151;
        box-shadow: 0 14px 35px rgba(2, 6, 23, 0.55);
    }
    .dark .ce-popover {
        background: rgba(17, 24, 39, 0.96);
        border-color: #374151;
    }
    .dark .ce-popover__item:hover,
    .dark .ce-conversion-tool:hover {
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
