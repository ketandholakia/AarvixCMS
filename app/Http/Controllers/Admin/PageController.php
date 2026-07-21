<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminCrudController;
use App\Models\Page;
use App\Http\Requests\Admin\PageRequest;
use Illuminate\Http\Request;

class PageController extends AdminCrudController
{
    protected function getModelClass(): string
    {
        return Page::class;
    }

    protected function getViewPrefix(): string
    {
        return 'admin.pages';
    }

    protected function getRoutePrefix(): string
    {
        return 'admin.pages';
    }

    protected function getFormRequestClass(): ?string
    {
        return PageRequest::class;
    }

    protected function getSearchableColumns(): array
    {
        return ['title', 'slug'];
    }

    protected function indexQuery($query)
    {
        return $query->with('author')->latest();
    }

    protected function beforeStore(Request $request, array &$data): void
    {
        if (!isset($data['author_id'])) {
            $data['author_id'] = auth()->id();
        }
    }
}
