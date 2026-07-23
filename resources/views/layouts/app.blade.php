<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Aarvix CMS'))</title>
    @yield('meta')
    @yield('meta_description')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased">
    @include('partials.theme-preview-banner')
    <div class="{{ !empty($themePreviewActive) ? 'pt-16' : '' }}">
        @yield('content')
        @stack('scripts')
    </div>
</body>
</html>
