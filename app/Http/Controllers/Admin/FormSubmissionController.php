<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormSubmission;

class FormSubmissionController extends AdminCrudController
{
    protected function getModelClass(): string
    {
        return FormSubmission::class;
    }

    protected function getViewPrefix(): string
    {
        return 'admin.form_submissions';
    }

    protected function getRoutePrefix(): string
    {
        return 'admin.form_submissions';
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
        return []; // Admins do not create/edit submissions manually
    }
}
