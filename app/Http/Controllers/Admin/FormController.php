<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Form;

class FormController extends AdminCrudController
{
    protected function getModelClass(): string
    {
        return Form::class;
    }

    protected function getViewPrefix(): string
    {
        return 'admin.forms';
    }

    protected function getRoutePrefix(): string
    {
        return 'admin.forms';
    }

    protected function permissionMap(): array
    {
        return [
            'view'   => 'view_forms',
            'create' => 'manage_forms',
            'edit'   => 'manage_forms',
            'delete' => 'manage_forms',
        ];
    }

    protected function getValidationRules(\Illuminate\Http\Request $request, ?\Illuminate\Database\Eloquent\Model $model = null): array
    {
        $id = $model ? $model->id : null;
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:forms,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'fields' => 'nullable|array',
        ];
    }

    public function store(Request $request)
    {
        if ($request->has('fields') && is_string($request->fields)) {
            $request->merge(['fields' => json_decode($request->fields, true)]);
        }
        return parent::store($request);
    }

    public function update(Request $request, string $id)
    {
        if ($request->has('fields') && is_string($request->fields)) {
            $request->merge(['fields' => json_decode($request->fields, true)]);
        }
        return parent::update($request, $id);
    }
}
