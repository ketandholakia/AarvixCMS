<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminCrudController;
use App\Models\Post;
use App\Http\Requests\Admin\PostRequest;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class PostController extends AdminCrudController
{
    protected function getModelClass(): string
    {
        return Post::class;
    }

    protected function getViewPrefix(): string
    {
        return 'admin.posts';
    }

    protected function getRoutePrefix(): string
    {
        return 'admin.posts';
    }

    protected function getFormRequestClass(): ?string
    {
        return PostRequest::class;
    }

    protected function getSearchableColumns(): array
    {
        return ['title', 'slug', 'excerpt'];
    }

    protected function indexQuery($query)
    {
        return $query->with(['author', 'category'])->latest();
    }

    protected function beforeStore(Request $request, array &$data): void
    {
        // Enforce ownership based on auth if they aren't an admin/editor mapping users
        if (!isset($data['author_id'])) {
            $data['author_id'] = auth()->id();
        }
        // Tags are handled via afterStore/afterUpdate; remove from fillable data
        unset($data['tags']);
    }

    protected function afterStore(Request $request, \Illuminate\Database\Eloquent\Model $model): void
    {
        $model->tags()->sync($request->input('tags', []));
    }

    protected function afterUpdate(Request $request, \Illuminate\Database\Eloquent\Model $model): void
    {
        $model->tags()->sync($request->input('tags', []));
    }
}
