@extends('layouts.app')

@section('title', $page->title)

@section('content')
<div class="bg-white dark:bg-gray-900 transition-colors">
    <!-- Landing Page (No Header, No Container, 100% Custom) -->
    <div class="w-full">
        {!! app(\App\Services\BlockParser::class)->parse($page->body) !!}
    </div>
</div>
@endsection
