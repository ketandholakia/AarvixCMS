<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminCrudController;
use App\Models\Tag;
use App\Http\Requests\Admin\TagRequest;
use Illuminate\Http\Request;

class TagController extends AdminCrudController
{
    protected function getModelClass(): string
    {
        return Tag::class;
    }

    protected function getViewPrefix(): string
    {
        return 'admin.tags';
    }

    protected function getRoutePrefix(): string
    {
        return 'admin.tags';
    }

    protected function getFormRequestClass(): ?string
    {
        return TagRequest::class;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'slug'];
    }

    protected function indexQuery($query)
    {
        return $query->withCount('posts')->orderBy('name');
    }
}
