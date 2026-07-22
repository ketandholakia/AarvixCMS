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

    protected function authorizeOwnership(string $ability, \Illuminate\Database\Eloquent\Model $model): void
    {
        $this->authorize($ability, $model);
    }

    protected function beforeStore(Request $request, array &$data): void
    {
        if (!isset($data['author_id'])) {
            $data['author_id'] = auth()->id();
        }
        unset($data['translations']);
    }

    protected function beforeUpdate(Request $request, \Illuminate\Database\Eloquent\Model $model, array &$data): void
    {
        unset($data['translations']);
    }

    protected function afterStore(Request $request, \Illuminate\Database\Eloquent\Model $model): void
    {
        $this->syncTranslations($request, $model);
    }

    protected function afterUpdate(Request $request, \Illuminate\Database\Eloquent\Model $model): void
    {
        $this->syncTranslations($request, $model);
    }

    protected function syncTranslations(Request $request, \Illuminate\Database\Eloquent\Model $model): void
    {
        $translations = $request->input('translations', []);
        
        foreach ($translations as $locale => $data) {
            if (empty(array_filter($data))) {
                continue;
            }

            $model->translations()->updateOrCreate(
                ['locale' => $locale],
                $data
            );
        }
    }
}
