<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Load roots with children
        $categories = Category::whereNull('parent_id')
            ->with(['children' => function($q) {
                $q->withCount('posts');
            }])
            ->withCount('posts')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(string $slug)
    {
        $category = Category::with(['children' => function($q) {
                $q->withCount('posts');
            }])
            ->withCount('posts')
            ->where('slug', $slug)
            ->firstOrFail();

        return new CategoryResource($category);
    }

    public function store(\App\Http\Requests\Api\CategoryRequest $request)
    {
        abort_unless($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Editor'), 403);

        $category = Category::create($request->validated());

        return new CategoryResource($category);
    }

    public function update(\App\Http\Requests\Api\CategoryRequest $request, Category $category)
    {
        abort_unless($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Editor'), 403);

        $category->update($request->validated());

        return new CategoryResource($category);
    }

    public function destroy(Request $request, Category $category)
    {
        abort_unless($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Editor'), 403);

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
