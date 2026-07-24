@php
    $aiEnabled = filter_var(app(\App\Services\SettingService::class)->get('ai.enabled', config('ai.enabled', false)), FILTER_VALIDATE_BOOLEAN);
    $aiConfig = [
        'enabled' => $aiEnabled,
        'context' => $aiContext ?? null,
        'recordId' => $aiRecordId ?? null,
        'contentTypeSlug' => $aiContentTypeSlug ?? null,
        'fieldName' => $fieldName ?? null,
    ];
@endphp

<div class="rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-cyan-50 p-4 shadow-sm dark:border-indigo-900/40 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950" x-data='aiWriterPanel(@js($aiConfig))'>
    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">AI Writer</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Generate a preview before you apply anything.</div>
        </div>
        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $aiEnabled ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
            {{ $aiEnabled ? 'Ready' : 'Disabled' }}
        </span>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <select x-model="operation" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            <option value="rewrite">Rewrite</option>
            <option value="shorten">Shorten</option>
            <option value="expand">Expand</option>
            <option value="summarize">Summarize</option>
            <option value="grammar">Grammar</option>
        </select>

        <select x-model="scope" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            <option value="document">Whole document</option>
            <option value="selection">Selected text</option>
        </select>

        <input x-model="tone" type="text" placeholder="Tone, e.g. friendly" class="rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">

        <button type="button" @click="generate" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
            <span x-show="!loading">Generate Preview</span>
            <span x-show="loading" style="display:none;">Generating...</span>
        </button>
    </div>

    <div class="mt-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">Selection is used when available; otherwise the whole document is sent.</p>
    </div>

    <div x-show="error" class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300" style="display:none;" x-text="error"></div>

    <div x-show="preview" class="mt-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950" style="display:none;">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white">Preview</div>
                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="preview.summary || 'Review the suggestion before applying it.'"></div>
            </div>
            <button type="button" @click="copyPreview" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Copy</button>
        </div>
        <div class="mt-3 space-y-3">
            <template x-for="(block, index) in (preview.blocks || [])" :key="index">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" x-text="block.type"></div>
                    <template x-if="block.type === 'image'">
                        <div class="mt-2 space-y-2">
                            <img
                                :src="block.data?.file?.url || ''"
                                :alt="block.data?.alt || block.data?.caption || ''"
                                class="max-h-64 w-auto rounded-xl border border-gray-200 object-contain dark:border-gray-700"
                            >
                            <div class="text-xs text-gray-500 dark:text-gray-400" x-show="block.data?.caption" x-text="block.data?.caption || ''"></div>
                        </div>
                    </template>
                    <template x-if="block.type !== 'image'">
                        <div class="mt-2 whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200" x-text="block.data?.text || block.data?.code || block.data?.caption || ''"></div>
                    </template>
                </div>
            </template>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" @click="applyPreview('replace')" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Replace</button>
            <button type="button" @click="applyPreview('insert')" class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700" x-show="preview.actions?.includes('insert')" style="display:none;">Insert</button>
            <button type="button" @click="cancelPreview" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900">Cancel</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(() => {
    const registerAiWriterPanel = () => {
        if (window.__aiWriterPanelRegistered) {
            return;
        }

        window.__aiWriterPanelRegistered = true;

        Alpine.data('aiWriterPanel', (config) => ({
        operation: 'rewrite',
        scope: 'document',
        tone: '',
        loading: false,
        error: '',
        preview: null,
        config,
        async generate() {
            if (! this.config.enabled) {
                this.error = 'AI is disabled in settings.';
                return;
            }

            this.loading = true;
            this.error = '';
            this.preview = null;

            try {
                const selection = window.getSelection ? String(window.getSelection()) : '';
                const textarea = this.$root.closest('form')?.querySelector('textarea[name="body"], textarea[name$="[body]"]');
                const documentValue = textarea ? textarea.value : '';
                const scope = this.scope || 'document';

                if (scope === 'selection' && (! selection || ! selection.trim())) {
                    throw new Error('Highlight text first or switch to whole document.');
                }

                const response = await fetch('{{ route('admin.ai.writer.generate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        context: this.config.context,
                        record_id: this.config.recordId || null,
                        content_type_slug: this.config.contentTypeSlug || null,
                        operation: this.operation,
                        scope,
                        tone: this.tone || null,
                        selection: scope === 'selection' ? (selection || null) : null,
                        document: documentValue,
                    }),
                });

                const payload = await response.json();

                if (! response.ok) {
                    throw new Error(payload.message || 'AI writer request failed.');
                }

                this.preview = payload.preview || null;
                if (this.preview && (! this.preview.blocks || ! this.preview.blocks.length) && payload.suggestion) {
                    this.preview.blocks = [{
                        type: 'paragraph',
                        data: { text: payload.suggestion },
                    }];
                }
            } catch (error) {
                this.error = error.message || 'AI writer request failed.';
            } finally {
                this.loading = false;
            }
        },
        copyPreview() {
            if (! this.preview?.plain_text) {
                return;
            }

            navigator.clipboard?.writeText(this.preview.plain_text);
        },
        applyPreview(mode) {
            if (! this.preview?.blocks?.length) {
                return;
            }

            const editor = window.AarvixEditorJs?.[this.config.fieldName];

            if (editor && typeof editor.applyPreview === 'function') {
                editor.applyPreview(this.preview.blocks, mode || this.preview.mode || 'replace')
                    .then(() => {
                        this.preview = null;
                    })
                    .catch((error) => {
                        this.error = error?.message || 'Unable to apply preview.';
                    });

                return;
            }

            const textarea = this.$root.closest('form')?.querySelector('textarea[name="' + this.config.fieldName + '"], textarea[name$="[' + this.config.fieldName + ']"]');

            if (! textarea) {
                this.error = 'Editor instance is not ready yet.';
                return;
            }

            const blockText = this.preview.blocks
                .map((block) => {
                    const data = block?.data || {};

                    if (block?.type === 'image') {
                        return data.caption || data.alt || data.file?.url || '';
                    }

                    return data.text || data.code || data.caption || '';
                })
                .filter((value) => typeof value === 'string' && value.trim() !== '')
                .join('\n\n');

            const currentValue = textarea.value || '';
            textarea.value = mode === 'insert' && currentValue.trim() !== ''
                ? [currentValue.trim(), blockText].filter(Boolean).join('\n\n')
                : blockText;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.dispatchEvent(new Event('change', { bubbles: true }));
            this.preview = null;
        },
        cancelPreview() {
            this.preview = null;
        },
        }));

        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(document.body);
        }
    };

    if (window.Alpine) {
        registerAiWriterPanel();
    } else {
        document.addEventListener('alpine:init', registerAiWriterPanel, { once: true });
    }
})();
</script>
@endpush
@endonce
