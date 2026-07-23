<div class="space-y-8">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="font-heading text-lg font-bold text-gray-900 dark:text-white mb-4">About Us</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">
            This is the default sidebar section. Replace it in the active theme to add widgets, promos, recent posts, or author bios.
        </p>
    </div>

    <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="font-heading text-lg font-bold text-gray-900 dark:text-white mb-4">Categories</h3>
        <ul class="space-y-2 text-sm">
            @foreach(\App\Models\Category::take(5)->get() as $cat)
                <li>
                    <a href="{{ route('category.show', $cat->slug) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ $cat->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
