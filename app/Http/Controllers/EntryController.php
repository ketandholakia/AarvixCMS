<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Entry;
use App\Services\ContentTypeRegistry;

class EntryController extends Controller
{
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

        // Theme-overrideable view resolution:
        // themes/{active}/views/entries/{type_slug}/show.blade.php → fallback default
        $view = "entries.{$type_slug}.show";
        if (!view()->exists($view)) {
            $view = 'entries.default-show';
        }

        return view($view, compact('entry', 'contentType'));
    }
}
