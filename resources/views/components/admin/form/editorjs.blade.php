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
    data-editorjs-media-endpoint="{{ route('admin.media.index') }}"
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
            'fieldName' => $name,
        ])
    @endif

    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            class="inline-flex items-center rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition-colors hover:bg-indigo-100 dark:border-indigo-900/40 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-900/40"
            data-editorjs-open-media
        >
            Media Library
        </button>
        <button
            type="button"
            class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
            data-editorjs-upload-trigger
        >
            Upload Image
        </button>
        <input type="file" accept="image/*" class="hidden" data-editorjs-file-input>
    </div>
    
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

    <div class="hidden fixed inset-0 z-50" data-editorjs-media-modal aria-hidden="true">
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" data-editorjs-close-media></div>
        <div class="relative mx-auto mt-10 flex max-h-[85vh] w-[min(1100px,92vw)] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-950">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select from Media Library</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Choose an existing image and insert it as an Editor.js image block.</p>
                </div>
                <button
                    type="button"
                    class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900"
                    data-editorjs-close-media
                >
                    Close
                </button>
            </div>
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <form class="flex flex-col gap-3 sm:flex-row" data-editorjs-media-search-form>
                    <input
                        type="text"
                        class="flex-1 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        placeholder="Search media by filename or alt text"
                        data-editorjs-media-search
                    >
                    <button
                        type="submit"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700"
                    >
                        Search
                    </button>
                </form>
            </div>
            <div class="min-h-[320px] flex-1 overflow-y-auto px-5 py-5">
                <div class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300" data-editorjs-media-error></div>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4" data-editorjs-media-grid></div>
                <div class="hidden py-10 text-center text-sm text-gray-500 dark:text-gray-400" data-editorjs-media-empty>No images found.</div>
                <div class="hidden py-10 text-center text-sm text-gray-500 dark:text-gray-400" data-editorjs-media-loading>Loading media...</div>
            </div>
            <div class="flex items-center justify-between gap-4 border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400" data-editorjs-media-summary></p>
                <div class="flex items-center gap-2" data-editorjs-media-pagination></div>
            </div>
        </div>
    </div>
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

            const normalizeImagePayload = (payload) => ({
                type: 'image',
                data: {
                    file: {
                        url: payload?.file?.url || payload?.location || '',
                        media_id: payload?.file?.media_id || payload?.media_id || null,
                    },
                    caption: payload?.caption || '',
                    withBorder: false,
                    withBackground: false,
                    stretched: false,
                },
            });

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

                const mediaEndpoint = root.dataset.editorjsMediaEndpoint || '';
                const mediaModal = root.querySelector('[data-editorjs-media-modal]');
                const mediaGrid = root.querySelector('[data-editorjs-media-grid]');
                const mediaError = root.querySelector('[data-editorjs-media-error]');
                const mediaEmpty = root.querySelector('[data-editorjs-media-empty]');
                const mediaLoading = root.querySelector('[data-editorjs-media-loading]');
                const mediaSummary = root.querySelector('[data-editorjs-media-summary]');
                const mediaPagination = root.querySelector('[data-editorjs-media-pagination]');
                const mediaSearchForm = root.querySelector('[data-editorjs-media-search-form]');
                const mediaSearchInput = root.querySelector('[data-editorjs-media-search]');
                const mediaOpenButton = root.querySelector('[data-editorjs-open-media]');
                const mediaCloseButtons = root.querySelectorAll('[data-editorjs-close-media]');
                const uploadTrigger = root.querySelector('[data-editorjs-upload-trigger]');
                const uploadInput = root.querySelector('[data-editorjs-file-input]');

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

                const uploadImageByFile = async (file) => {
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
                };

                const uploadImageByUrl = async (url) => {
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
                };

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
                                uploadByFile: uploadImageByFile,
                                uploadByUrl: uploadImageByUrl,
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
                let mediaPage = 1;
                let mediaSearch = '';

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

                const syncTextarea = async () => {
                    await editor.isReady;
                    const outputData = await editor.save();
                    documentData = outputData;
                    textarea.value = JSON.stringify(outputData);
                    return outputData;
                };

                const syncTextareaOrFallback = async () => {
                    try {
                        return await syncTextarea();
                    } catch (error) {
                        console.error('Editor.js sync failed, falling back to the raw textarea value.', error);
                        return textarea.value || '';
                    }
                };

                const insertImageBlock = async (payload) => {
                    const block = normalizeImagePayload(payload);
                    await editor.blocks.insert(block.type, block.data);
                    await syncTextarea();
                };

                const setMediaError = (message = '') => {
                    if (! mediaError) {
                        return;
                    }

                    mediaError.textContent = message;
                    mediaError.classList.toggle('hidden', ! message);
                };

                const setMediaLoading = (loading) => {
                    mediaLoading?.classList.toggle('hidden', ! loading);
                    mediaGrid?.classList.toggle('opacity-50', loading);
                };

                const closeMediaModal = () => {
                    mediaModal?.classList.add('hidden');
                    mediaModal?.setAttribute('aria-hidden', 'true');
                    setMediaError('');
                };

                const openMediaModal = async () => {
                    mediaModal?.classList.remove('hidden');
                    mediaModal?.setAttribute('aria-hidden', 'false');
                    await loadMediaLibrary(1, mediaSearchInput?.value || '');
                };

                const renderMediaCards = (items) => {
                    if (! mediaGrid) {
                        return;
                    }

                    mediaGrid.innerHTML = '';

                    items.forEach((media) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'group overflow-hidden rounded-2xl border border-gray-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-700';
                        button.innerHTML = `
                            <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-gray-800">
                                <img src="${media.url}" alt="${media.alt_text || media.filename}" class="h-full w-full object-cover">
                            </div>
                            <div class="space-y-1 p-3">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">${media.filename}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">${media.alt_text || 'No alt text'}</p>
                            </div>
                        `;
                        button.addEventListener('click', async () => {
                            try {
                                setMediaError('');
                                await insertImageBlock({
                                    file: {
                                        url: media.url,
                                        media_id: media.id,
                                    },
                                    caption: media.caption || '',
                                });
                                closeMediaModal();
                            } catch (error) {
                                setMediaError(error.message || 'Unable to insert the selected image.');
                            }
                        });

                        mediaGrid.appendChild(button);
                    });
                };

                const renderMediaPagination = (payload) => {
                    if (! mediaPagination) {
                        return;
                    }

                    mediaPagination.innerHTML = '';

                    const makeButton = (label, page, disabled = false) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = label;
                        button.disabled = disabled;
                        button.className = 'rounded-xl border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900';
                        button.addEventListener('click', () => loadMediaLibrary(page, mediaSearch));
                        mediaPagination.appendChild(button);
                    };

                    makeButton('Previous', payload.current_page - 1, payload.current_page <= 1);
                    makeButton('Next', payload.current_page + 1, payload.current_page >= payload.last_page);
                };

                const loadMediaLibrary = async (page = 1, search = '') => {
                    if (! mediaEndpoint) {
                        return;
                    }

                    mediaPage = page;
                    mediaSearch = search.trim();
                    setMediaError('');
                    setMediaLoading(true);

                    try {
                        const url = new URL(mediaEndpoint, window.location.origin);
                        url.searchParams.set('page', String(mediaPage));
                        if (mediaSearch) {
                            url.searchParams.set('search', mediaSearch);
                        }

                        const response = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });

                        const payload = await response.json();

                        if (! response.ok) {
                            throw new Error(payload.message || 'Unable to load the media library.');
                        }

                        const items = Array.isArray(payload.data)
                            ? payload.data.filter((media) => (media.mime_type || '').startsWith('image/'))
                            : [];

                        renderMediaCards(items);
                        renderMediaPagination(payload);
                        mediaEmpty?.classList.toggle('hidden', items.length > 0);

                        if (mediaSummary) {
                            mediaSummary.textContent = items.length
                                ? `Showing ${payload.from ?? 1}-${payload.to ?? items.length} of ${payload.total ?? items.length} images`
                                : 'No matching images in the media library.';
                        }
                    } catch (error) {
                        renderMediaCards([]);
                        mediaPagination && (mediaPagination.innerHTML = '');
                        mediaEmpty?.classList.add('hidden');
                        if (mediaSummary) {
                            mediaSummary.textContent = '';
                        }
                        setMediaError(error.message || 'Unable to load the media library.');
                    } finally {
                        setMediaLoading(false);
                    }
                };

                window.AarvixEditorJs[name] = {
                    sync: syncTextareaOrFallback,
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
                    },
                    insertImageBlock,
                };

                mediaOpenButton?.addEventListener('click', openMediaModal);
                mediaCloseButtons.forEach((button) => button.addEventListener('click', closeMediaModal));
                mediaSearchForm?.addEventListener('submit', (event) => {
                    event.preventDefault();
                    loadMediaLibrary(1, mediaSearchInput?.value || '');
                });

                uploadTrigger?.addEventListener('click', () => uploadInput?.click());
                uploadInput?.addEventListener('change', async (event) => {
                    const [file] = event.target.files || [];
                    if (! file) {
                        return;
                    }

                    try {
                        setMediaError('');
                        const payload = await uploadImageByFile(file);
                        await insertImageBlock(payload);
                    } catch (error) {
                        setMediaError(error.message || 'Unable to upload the selected image.');
                    } finally {
                        event.target.value = '';
                    }
                });

                const form = textarea.closest('form');
                if (form && form.dataset.editorjsSubmitInitialized !== 'true') {
                    form.dataset.editorjsSubmitInitialized = 'true';

                    form.addEventListener('submit', async (event) => {
                        if (form.dataset.editorjsSubmitting === 'true') {
                            return;
                        }

                        event.preventDefault();
                        form.dataset.editorjsSubmitting = 'true';

                        try {
                            const roots = Array.from(form.querySelectorAll('[data-editorjs-root]'));

                            for (const currentRoot of roots) {
                                const fieldName = currentRoot.dataset.editorjsName || '';
                                const editorApi = window.AarvixEditorJs?.[fieldName];

                                if (editorApi && typeof editorApi.sync === 'function') {
                                    await editorApi.sync();
                                }
                            }

                            form.submit();
                        } catch (error) {
                            console.error('Unable to submit the form because one or more Editor.js fields failed to sync.', error);
                        } finally {
                            form.dataset.editorjsSubmitting = 'false';
                        }
                    });
                }
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
