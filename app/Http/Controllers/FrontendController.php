<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Page;

class FrontendController extends Controller
{
    public function index(Request $request, $category_slug = null, $tag_slug = null)
    {
        $query = Post::with(['author', 'category'])
            ->where('status', 'published')
            ->where(function($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });

        // Resolve optional params from route since they are mutually exclusive in our routes
        $categorySlug = $request->route('category_slug');
        $tagSlug = $request->route('tag_slug');

        $activeCategory = null;
        if ($categorySlug) {
            $activeCategory = \App\Models\Category::where('slug', $categorySlug)->firstOrFail();
            $query->where('category_id', $activeCategory->id);
        }

        $activeTag = null;
        if ($tagSlug) {
            $activeTag = \App\Models\Tag::where('slug', $tagSlug)->firstOrFail();
            $query->whereHas('tags', function ($q) use ($activeTag) {
                $q->where('tags.id', $activeTag->id);
            });
        }

        $posts = $query->latest('published_at')->latest()->paginate(10);

        return view('frontend.index', compact('posts', 'activeCategory', 'activeTag'));
    }

    public function showPost($slug)
    {
        $post = Post::with(['author', 'category'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $relatedPosts = collect();
        if ($post->category_id) {
            $relatedPosts = Post::with(['author', 'category'])
                ->where('category_id', $post->category_id)
                ->where('id', '!=', $post->id)
                ->where('status', 'published')
                ->where(function($q) {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->latest('published_at')
                ->take(3)
                ->get();
        }

        return view('frontend.post', compact('post', 'relatedPosts'));
    }

    public function showPage($slug)
    {
        $page = Page::with('author')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $template = $page->template ?: 'default';
        // if a specific template exists like frontend.pages.full-width, use it. Otherwise fallback.
        $view = "frontend.page";
        if (view()->exists("frontend.pages.{$template}")) {
            $view = "frontend.pages.{$template}";
        }

        return view($view, compact('page'));
    }
}
