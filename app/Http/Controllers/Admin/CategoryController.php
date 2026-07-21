<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminCrudController;
use App\Models\Category;
use App\Http\Requests\Admin\CategoryRequest;
use Illuminate\Http\Request;

class CategoryController extends AdminCrudController
{
    protected function getModelClass(): string
    {
        return Category::class;
    }

    protected function getViewPrefix(): string
    {
        return 'admin.categories';
    }

    protected function getRoutePrefix(): string
    {
        return 'admin.categories';
    }

    protected function getFormRequestClass(): ?string
    {
        return CategoryRequest::class;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'slug', 'description'];
    }

    protected function indexQuery($query)
    {
        // Show tree structure or simple list ordered by sort_order
        return $query->with('parent')->orderBy('sort_order');
    }
}
