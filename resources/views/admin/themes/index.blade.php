@extends('layouts.admin')

@section('header', 'Themes')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Installed Themes</h3>
    <p class="text-sm text-gray-500">Manage the look and feel of your frontend.</p>
</div>

<div class="mb-8 grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Theme Settings for {{ $activeTheme }}</h3>
        @if(empty($settingsSchema) && empty($sections))
            <p class="text-sm text-gray-500">This theme does not expose editable settings or sections.</p>
        @else
            <form id="theme-settings-form" action="{{ route('admin.themes.settings.update') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                @method('PUT')
                @foreach($settingsSchema as $field)
                    @php
                        $key = $field['key'] ?? '';
                        $label = $field['label'] ?? $key;
                        $type = $field['type'] ?? 'text';
                    @endphp

                    @if($type === 'boolean')
                        <label class="flex items-center gap-3 mt-6 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked(($settings[$key] ?? false)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            {{ $label }}
                        </label>
                    @else
                        <x-admin.form.input
                            :name="$key"
                            :label="$label"
                            :type="$type === 'url' ? 'url' : 'text'"
                            :value="$settings[$key] ?? ''"
                            :placeholder="$field['placeholder'] ?? ''"
                        />
                    @endif
                @endforeach

                @foreach($sections as $section)
                    @php
                        $key = $section['key'] ?? '';
                        $label = $section['label'] ?? $key;
                    @endphp

                    <div class="md:col-span-2">
                        <x-admin.form.textarea
                            :name="'sections[' . $key . ']'"
                            :label="$label"
                            :value="$sectionContent[$key] ?? ''"
                            rows="8"
                        />
                    </div>
                @endforeach

                <div class="md:col-span-2 flex items-center gap-3">
                    <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                        Save Theme Settings
                    </button>
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('theme-preview-refresh'))" class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                        Refresh Preview
                    </button>
                    <span id="theme-autosave-status" class="text-sm text-gray-500"></span>
                </div>
                <div id="theme-autosave-error" class="md:col-span-2 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200"></div>
            </form>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden flex flex-col min-h-[720px]">
        <div class="flex items-center justify-between gap-3 p-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Live Preview</h3>
                <p class="text-sm text-gray-500">Preview reflects the current session theme and saved section content.</p>
            </div>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors">
                Open in new tab
            </a>
        </div>
        <div class="flex-1 bg-gray-100 dark:bg-gray-950">
            <iframe
                id="theme-preview-frame"
                src="{{ route('home') }}"
                class="w-full h-full min-h-[640px] bg-white"
                title="Theme preview"
            ></iframe>
        </div>
    </div>
</div>

<script>
    (() => {
        const form = document.getElementById('theme-settings-form');
        const frame = document.getElementById('theme-preview-frame');
        const status = document.getElementById('theme-autosave-status');
        const errorBox = document.getElementById('theme-autosave-error');
        if (!form) return;

        let timer = null;
        let inFlight = false;

        const setStatus = (text) => {
            if (status) {
                status.textContent = text;
            }
        };

        const clearError = () => {
            if (errorBox) {
                errorBox.textContent = '';
                errorBox.classList.add('hidden');
            }
        };

        const showError = (text) => {
            if (errorBox) {
                errorBox.textContent = text;
                errorBox.classList.remove('hidden');
            }
        };

        const refreshPreview = () => {
            if (frame && frame.contentWindow) {
                frame.contentWindow.location.reload();
            }
        };

        const save = async () => {
            if (inFlight) return;
            inFlight = true;
            setStatus('Saving...');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });

                if (!response.ok) {
                    const payload = await response.json().catch(() => null);
                    const message = payload?.message
                        || Object.values(payload?.errors || {}).flat().shift()
                        || 'Save failed';
                    throw new Error(message);
                }

                clearError();
                setStatus('Saved');
                refreshPreview();
                window.dispatchEvent(new CustomEvent('theme-preview-refresh'));
            } catch (error) {
                setStatus('Save failed');
                showError(error.message || 'Save failed');
            } finally {
                inFlight = false;
                setTimeout(() => setStatus(''), 2000);
            }
        };

        form.addEventListener('input', () => {
            clearTimeout(timer);
            clearError();
            setStatus('Unsaved changes');
            timer = setTimeout(save, 700);
        });

        form.addEventListener('change', () => {
            clearTimeout(timer);
            clearError();
            timer = setTimeout(save, 300);
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            clearTimeout(timer);
            save();
        });
    })();

    window.addEventListener('theme-preview-refresh', () => {
        const frame = document.getElementById('theme-preview-frame');
        if (frame) {
            frame.contentWindow.location.reload();
        }
    });
</script>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($themes as $theme)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border {{ $theme['is_active'] ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-100 dark:border-gray-800' }} shadow-sm overflow-hidden flex flex-col">
            <div class="aspect-video bg-gray-100 dark:bg-gray-800 relative">
                @if(file_exists($theme['path'] . '/screenshot.png'))
                    <img src="{{ asset('themes/' . $theme['id'] . '/screenshot.png') }}" class="w-full h-full object-cover">
                @else
                    <div class="absolute inset-0 flex items-center justify-center text-gray-400 overflow-hidden">
                        <svg width="48" height="48" class="opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                @endif
                
                @if($theme['is_active'])
                    <div class="absolute top-2 right-2 bg-indigo-500 text-white text-xs px-2 py-1 rounded-md font-medium shadow-sm">
                        Active
                    </div>
                @endif
            </div>
            
            <div class="p-5 flex-1 flex flex-col">
                <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $theme['name'] }}</h4>
                <p class="text-sm text-gray-500 mt-1 mb-4 flex-1">{{ $theme['description'] }}</p>
                
                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                    <span>v{{ $theme['version'] }}</span>
                    <span>By {{ $theme['author'] }}</span>
                </div>

                @if(!empty($theme['parent']))
                    <p class="text-xs text-gray-400 mb-4">Child of {{ $theme['parent'] }}</p>
                @endif
                
                @if(!$theme['is_active'])
                    <div class="space-y-2">
                        <form action="{{ route('admin.themes.activate') }}" method="POST">
                            @csrf
                            <input type="hidden" name="theme" value="{{ $theme['id'] }}">
                            <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                Activate
                            </button>
                        </form>
                        <form action="{{ route('admin.themes.preview') }}" method="POST">
                            @csrf
                            <input type="hidden" name="theme" value="{{ $theme['id'] }}">
                            <button type="submit" class="w-full py-2 px-4 border border-gray-300 dark:border-gray-700 rounded-xl shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                Preview
                            </button>
                        </form>
                    </div>
                @else
                    <div class="space-y-2">
                        <button disabled class="w-full py-2 px-4 border border-gray-300 dark:border-gray-700 rounded-xl shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 cursor-not-allowed">
                            Currently Active
                        </button>
                        @if(session('preview_theme'))
                            <form action="{{ route('admin.themes.preview.clear') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2 px-4 border border-gray-300 dark:border-gray-700 rounded-xl shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    Clear Preview
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-8 text-center">
            <p class="text-gray-500 dark:text-gray-400">No themes found. Create a theme in the <code>/themes</code> directory.</p>
        </div>
    @endforelse
</div>
@endsection
