<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContentType;
use App\Models\Permission;
use App\Models\Role;
use App\Http\Requests\Admin\ContentTypeRequest;
use App\Services\ContentTypeRegistry;
use Illuminate\Http\Request;

class ContentTypeController extends AdminCrudController
{
    protected function getModelClass(): string
    {
        return ContentType::class;
    }

    protected function getViewPrefix(): string
    {
        return 'admin.content-types';
    }

    protected function getRoutePrefix(): string
    {
        return 'admin.content-types';
    }

    protected function getFormRequestClass(): ?string
    {
        return ContentTypeRequest::class;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'slug'];
    }

    protected function permissionMap(): array
    {
        return [
            'view'   => 'manage_content_types',
            'create' => 'manage_content_types',
            'edit'   => 'manage_content_types',
            'delete' => 'manage_content_types',
        ];
    }

    // ─── Override store to auto-seed permissions ───────────────────────────────

    public function store(Request $request)
    {
        $this->authorizePermission('create');

        $formRequest = app(ContentTypeRequest::class);
        $data = $formRequest->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $contentType = ContentType::create($data);

        app(ContentTypeRegistry::class)->invalidate();

        return redirect()->route('admin.content-types.index')
            ->with('success', "Content type '{$contentType->name}' created successfully.");
    }

    public function update(Request $request, string $id)
    {
        $this->authorizePermission('edit');

        $contentType = ContentType::findOrFail($id);

        if ($contentType->is_system) {
            return redirect()->back()->with('error', 'System content types cannot be modified.');
        }

        $formRequest = app(ContentTypeRequest::class);
        $data = $formRequest->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $contentType->update($data);
        app(ContentTypeRegistry::class)->invalidate();

        return redirect()->route('admin.content-types.index')
            ->with('success', "Content type '{$contentType->name}' updated successfully.");
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizePermission('delete');

        $contentType = ContentType::findOrFail($id);

        if ($contentType->is_system) {
            return redirect()->back()->with('error', 'System content types cannot be deleted.');
        }

        if ($contentType->entries()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete a content type that has existing entries. Delete or move the entries first.');
        }

        $contentType->delete();
        app(ContentTypeRegistry::class)->invalidate();

        return redirect()->route('admin.content-types.index')
            ->with('success', 'Content type deleted successfully.');
    }

    // ─── Field Builder ─────────────────────────────────────────────────────────

    public function fieldBuilder(string $id)
    {
        $this->authorizePermission('edit');

        $contentType = ContentType::findOrFail($id);

        return view('admin.content-types.field-builder', compact('contentType'));
    }

    public function saveSchema(Request $request, string $id)
    {
        $this->authorizePermission('edit');

        $contentType = ContentType::findOrFail($id);

        $data = $request->validate([
            'fields_schema'            => ['nullable', 'array'],
            'fields_schema.*.key'      => ['required', 'string', 'max:60', 'regex:/^[a-z_]+$/'],
            'fields_schema.*.label'    => ['required', 'string', 'max:100'],
            'fields_schema.*.type'     => ['required', 'in:text,textarea,select,checkbox,date,media,number,url,email'],
            'fields_schema.*.required' => ['nullable', 'boolean'],
            'fields_schema.*.options'  => ['nullable', 'string'],
        ]);

        $contentType->update(['fields_schema' => $data['fields_schema'] ?? []]);
        app(ContentTypeRegistry::class)->invalidate();

        return redirect()->route('admin.content-types.index')
            ->with('success', "Field schema for '{$contentType->name}' saved.");
    }

    // ─── Private ──────────────────────────────────────────────────────────────

}
