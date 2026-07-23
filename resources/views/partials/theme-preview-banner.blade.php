@if(!empty($themePreviewActive))
    <div class="fixed inset-x-0 top-0 z-[60] border-b border-amber-300 bg-amber-100 text-amber-950 shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-600"></span>
                <div>
                    <p class="text-sm font-semibold">Theme preview active</p>
                    <p class="text-xs text-amber-800">
                        Previewing {{ $themePreviewName ?? 'unknown theme' }}. Changes are visible only in this session.
                    </p>
                </div>
            </div>

            <form action="{{ $themePreviewExitUrl }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-full bg-amber-950 px-4 py-2 text-sm font-medium text-white hover:bg-amber-900 transition-colors">
                    Exit preview
                </button>
            </form>
        </div>
    </div>
@endif
