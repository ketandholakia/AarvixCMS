<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminCrudController;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class RoleController extends AdminCrudController
{
    protected function getModelClass(): string
    {
        return Role::class;
    }

    protected function getViewPrefix(): string
    {
        return 'admin.roles';
    }

    protected function getRoutePrefix(): string
    {
        return 'admin.roles';
    }

    protected function permissionMap(): array
    {
        return [
            'view'   => 'view_roles',
            'create' => 'edit_roles',
            'edit'   => 'edit_roles',
            'delete' => 'edit_roles',
        ];
    }

    protected function getSearchableColumns(): array
    {
        return ['name'];
    }

    protected function getValidationRules(Request $request, ?Model $model = null): array
    {
        $id = $model ? $model->id : null;
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ];
    }
    
    protected function indexQuery($query)
    {
        return $query->with('permissions');
    }

    protected function beforeStore(Request $request, array &$data): void
    {
        unset($data['permissions']);
    }

    protected function beforeUpdate(Request $request, Model $model, array &$data): void
    {
        unset($data['permissions']);
    }

    protected function afterStore(Request $request, Model $model): void
    {
        $model->permissions()->sync($request->input('permissions', []));
    }

    protected function afterUpdate(Request $request, Model $model): void
    {
        $model->permissions()->sync($request->input('permissions', []));
    }
}
