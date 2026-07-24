@php
    $aiEnabled = filter_var(app(\App\Services\SettingService::class)->get('ai.enabled', config('ai.enabled', false)), FILTER_VALIDATE_BOOLEAN);
    $aiSeoConfig = [
        'enabled' => $aiEnabled,
        'context' => $aiContext ?? null,
        'recordId' => $aiRecordId ?? null,
        'contentTypeSlug' => $aiContentTypeSlug ?? null,
        'titleField' => $aiTitleField ?? null,
        'bodyField' => $aiBodyField ?? null,
        'slugField' => $aiSlugField ?? null,
        'metaTitleField' => $aiMetaTitleField ?? null,
        'metaDescriptionField' => $aiMetaDescriptionField ?? null,
    ];
@endphp

<div class="rounded-2xl border border-cyan-200 bg-gradient-to-br from-cyan-50 via-white to-emerald-50 p-4 shadow-sm dark:border-cyan-900/40 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950" x-data='aiSeoPanel(@js($aiSeoConfig))'>
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-sm font-semibold text-cyan-700 dark:text-cyan-300">AI SEO</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Generate metadata for the fields below.</div>
        </div>
        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $aiEnabled ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
            {{ $aiEnabled ? 'Ready' : 'Disabled' }}
        </span>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" @click="generate" class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-60">
            <span x-show="!loading">Generate SEO</span>
            <span x-show="loading" style="display:none;">Generating...</span>
        </button>
        <button type="button" @click="applyPreview" class="rounded-xl border border-cyan-300 px-4 py-2 text-sm font-semibold text-cyan-700 hover:bg-cyan-50 dark:border-cyan-800 dark:text-cyan-300 dark:hover:bg-cyan-900/20" x-show="preview?.seo" style="display:none;">Apply</button>
        <button type="button" @click="clearPreview" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900" x-show="preview?.seo" style="display:none;">Dismiss</button>
    </div>

    <div class="mt-4" x-show="error" style="display:none;">
        <div class="rounded-xl bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300" x-text="error"></div>
    </div>

    <div class="mt-4 space-y-4" x-show="preview?.seo" style="display:none;">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="text-sm font-semibold text-gray-900 dark:text-white" x-text="preview.summary || 'SEO preview'"></div>
            <div class="mt-3 grid gap-3 text-sm">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Meta Title</div>
                    <div class="mt-1 text-gray-800 dark:text-gray-200" x-text="preview.seo.meta_title || ''"></div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="`Length: ${preview.seo.lengths?.meta_title ?? 0} characters`"></div>
                </div>
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Meta Description</div>
                    <div class="mt-1 whitespace-pre-wrap text-gray-800 dark:text-gray-200" x-text="preview.seo.meta_description || ''"></div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="`Length: ${preview.seo.lengths?.meta_description ?? 0} characters`"></div>
                </div>
                <div x-show="preview.seo.slug" style="display:none;">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Slug</div>
                    <div class="mt-1 text-gray-800 dark:text-gray-200" x-text="preview.seo.slug || ''"></div>
                </div>
                <div x-show="preview.seo.keywords?.length" style="display:none;">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Keywords</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="(keyword, index) in (preview.seo.keywords || [])" :key="index">
                            <span class="rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300" x-text="keyword"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="preview.seo.warnings?.length" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200" style="display:none;">
            <div class="font-semibold">Warnings</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <template x-for="(warning, index) in (preview.seo.warnings || [])" :key="index">
                    <li x-text="warning"></li>
                </template>
            </ul>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(() => {
    const registerAiSeoPanel = () => {
        if (window.__aiSeoPanelRegistered) {
            return;
        }

        window.__aiSeoPanelRegistered = true;

        Alpine.data('aiSeoPanel', (config) => ({
        loading: false,
        error: '',
        preview: null,
        config,
        getFieldValue(name) {
            if (! name) {
                return '';
            }

            const field = this.$root.closest('form')?.querySelector(`[name="${name}"]`);
            return field ? field.value : '';
        },
        setFieldValue(name, value, markManual = false) {
            if (! name) {
                return;
            }

            const field = this.$root.closest('form')?.querySelector(`[name="${name}"]`);
            if (! field) {
                return;
            }

            field.value = value || '';
            if (markManual && field.id === 'slug') {
                field.dataset.manual = 'true';
            }
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        },
        async generate() {
            if (! this.config.enabled) {
                this.error = 'AI is disabled in settings.';
                return;
            }

            this.loading = true;
            this.error = '';
            this.preview = null;

            try {
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
                        operation: 'seo',
                        scope: 'document',
                        title: this.getFieldValue(this.config.titleField),
                        document: this.getFieldValue(this.config.bodyField),
                    }),
                });

                const payload = await response.json();

                if (! response.ok) {
                    throw new Error(payload.message || 'SEO request failed.');
                }

                this.preview = payload.preview || null;
            } catch (error) {
                this.error = error.message || 'SEO request failed.';
            } finally {
                this.loading = false;
            }
        },
        applyPreview() {
            if (! this.preview?.seo) {
                return;
            }

            this.setFieldValue(this.config.metaTitleField, this.preview.seo.meta_title || '');
            this.setFieldValue(this.config.metaDescriptionField, this.preview.seo.meta_description || '');
            if (this.config.slugField) {
                this.setFieldValue(this.config.slugField, this.preview.seo.slug || '', true);
            }

            this.preview = null;
        },
        clearPreview() {
            this.preview = null;
        },
        }));

        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(document.body);
        }
    };

    if (window.Alpine) {
        registerAiSeoPanel();
    } else {
        document.addEventListener('alpine:init', registerAiSeoPanel, { once: true });
    }
})();
</script>
@endpush
@endonce
