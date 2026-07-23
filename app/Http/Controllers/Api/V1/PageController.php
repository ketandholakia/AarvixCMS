<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pages = Page::where('status', 'published')
            ->orderBy('title')
            ->get();

        return PageResource::collection($pages);
    }

    public function show(string $slug)
    {
        $page = Page::with(['author', 'tags'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return new PageResource($page);
    }

    public function store(\App\Http\Requests\Api\PageRequest $request)
    {
        $this->authorize('create', Page::class);

        $data = $request->validated();
        $data['author_id'] = $request->user()->id;

        $page = Page::create($data);

        return new PageResource($page);
    }

    public function update(\App\Http\Requests\Api\PageRequest $request, Page $page)
    {
        $this->authorize('update', $page);

        $page->update($request->validated());

        return new PageResource($page);
    }

    public function destroy(Request $request, Page $page)
    {
        $this->authorize('delete', $page);

        $page->delete();

        return response()->json(['message' => 'Page deleted successfully']);
    }
}
