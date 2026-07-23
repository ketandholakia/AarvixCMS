<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Entry;
use App\Services\ContentTypeRegistry;

class EntryController extends Controller
{
    private function resolveThemeView(string $view, ?string $fallback = null): string
    {
        if (view()->exists("theme::{$view}")) {
            return "theme::{$view}";
        }

        return $fallback ?? $view;
    }

    public function show(string $type_slug, string $slug)
    {
        $contentType = app(ContentTypeRegistry::class)->find($type_slug);

        if (!$contentType || !$contentType->is_active) {
            abort(404);
        }

        $entry = Entry::with(['author', 'category', 'featuredImage'])
            ->where('content_type_id', $contentType->id)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->firstOrFail();

        $view = $this->resolveThemeView("entries.{$type_slug}.show", 'entries.default-show');

        return view($view, compact('entry', 'contentType'));
    }
}
