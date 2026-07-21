<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (localStorage.getItem('darkMode') === null && window.matchMedia('(prefers-color-scheme: dark)').matches) }" 
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      x-bind:class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Aarvix CMS') }} Admin</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50 dark:bg-gray-950 dark:text-gray-100 transition-colors duration-300">
    
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
        <!-- Overlay -->
        <div x-show="sidebarOpen" x-transition.opacity 
             class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
             @click="sidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
               class="fixed inset-y-0 left-0 z-30 w-64 px-4 py-6 transition-transform duration-300 ease-in-out bg-white border-r border-gray-200 dark:bg-gray-900 dark:border-gray-800 lg:static lg:translate-x-0 lg:flex-shrink-0 flex flex-col shadow-xl lg:shadow-none">
            
            <!-- Logo area -->
            <div class="flex items-center justify-between mb-8 px-2">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 group">
                    <div class="p-2 bg-indigo-600 rounded-lg group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Aarvix<span class="text-indigo-600 dark:text-indigo-500">CMS</span></span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-500 hover:text-gray-900 dark:hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden p-1 custom-scrollbar">
                
                <x-admin.nav-item href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    </x-slot>
                    Dashboard
                </x-admin.nav-item>

                @if(auth()->user()->hasPermission('manage_settings'))
                    <a href="{{ route('admin.settings.index') }}" class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-gray-100 dark:bg-gray-800 text-indigo-600 dark:text-indigo-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Settings
                    </a>

                    <a href="{{ route('admin.forms.index') }}" class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ request()->routeIs('admin.forms.*') ? 'bg-gray-100 dark:bg-gray-800 text-indigo-600 dark:text-indigo-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Form Builder
                    </a>

                    <a href="{{ route('admin.form_submissions.index') }}" class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ request()->routeIs('admin.form_submissions.*') ? 'bg-gray-100 dark:bg-gray-800 text-indigo-600 dark:text-indigo-400' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Submissions
                    </a>
                @endif

                @if(auth()->user()->can('view_posts'))
                <x-admin.nav-item href="{{ route('admin.posts.index') }}" :active="request()->routeIs('admin.posts.*')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15M9 11l3 3L22 4"></path></svg>
                    </x-slot>
                    Posts
                </x-admin.nav-item>
                @endif

                @if(auth()->user()->can('view_pages'))
                <x-admin.nav-item href="{{ route('admin.pages.index') }}" :active="request()->routeIs('admin.pages.*')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </x-slot>
                    Pages
                </x-admin.nav-item>
                @endif
                
                @if(auth()->user()->can('view_categories'))
                <x-admin.nav-item href="{{ route('admin.categories.index') }}" :active="request()->routeIs('admin.categories.*')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    </x-slot>
                    Categories
                </x-admin.nav-item>
                @endif

                <x-admin.nav-item href="{{ route('admin.media.index') }}" :active="request()->routeIs('admin.media.*')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </x-slot>
                    Media
                </x-admin.nav-item>

                @if(auth()->user()->hasPermission('manage_users'))
                <x-admin.nav-item href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </x-slot>
                    Users
                </x-admin.nav-item>
                @endif
                
            </nav>


            <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full gap-3 px-3 py-2 text-sm font-medium text-red-600 transition-colors rounded-xl hover:bg-red-50 dark:hover:bg-red-900/30 dark:text-red-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden bg-gray-50/50 dark:bg-gray-950/50">
            <!-- Topbar -->
            <header class="flex items-center justify-between px-6 py-4 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 sticky top-0 z-10">
                <div class="flex items-center">
                    <button @click="sidebarOpen = true" class="mr-4 text-gray-500 lg:hidden hover:text-gray-900 dark:hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    </button>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        @yield('header', 'Dashboard')
                    </h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle -->
                    <button @click="darkMode = !darkMode" class="p-2 text-gray-500 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-400 transition-colors">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                        <svg x-show="darkMode" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </button>

                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ userMenuOpen: false }">
                        <button @click="userMenuOpen = !userMenuOpen" @click.away="userMenuOpen = false" class="flex items-center gap-3 p-1 pr-3 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            <img class="w-8 h-8 rounded-full border border-gray-200 dark:border-gray-700 object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&color=4f46e5&background=e0e7ff" alt="{{ auth()->user()->name }}">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        
                        <div x-show="userMenuOpen" x-transition.opacity style="display: none;" class="absolute right-0 w-48 py-1 mt-2 bg-white border border-gray-200 rounded-xl shadow-lg dark:bg-gray-900 dark:border-gray-800 ring-1 ring-black ring-opacity-5">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors">Profile settings</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto p-6 lg:p-8 custom-scrollbar">
                <div class="max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>

    @stack('scripts')
    <script>
        tinymce.init({
            selector: 'textarea.rich-editor',
            plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
            toolbar_mode: 'floating',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link',
            images_upload_url: '{{ route("admin.upload.image") }}',
            images_upload_credentials: true,
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            },
            skin: localStorage.getItem('darkMode') === 'true' ? 'oxide-dark' : 'oxide',
            content_css: localStorage.getItem('darkMode') === 'true' ? 'dark' : 'default',
        });

        // Re-init theme when toggling dark mode
        window.addEventListener('darkMode-toggled', (e) => {
            tinymce.remove();
            tinymce.init({
                selector: 'textarea.rich-editor',
                // ... (abridged for MVP)
                images_upload_url: '{{ route("admin.upload.image") }}',
                skin: e.detail ? 'oxide-dark' : 'oxide',
                content_css: e.detail ? 'dark' : 'default',
            });
        });
    </script>
</body>
</html>
