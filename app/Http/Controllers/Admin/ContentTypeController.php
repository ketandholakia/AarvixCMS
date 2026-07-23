<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ContentTypeRequest;
use App\Models\ContentType;
use App\Services\ContentTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
            'view' => 'manage_content_types',
            'create' => 'manage_content_types',
            'edit' => 'manage_content_types',
            'delete' => 'manage_content_types',
        ];
    }

    public function index(Request $request)
    {
        if ($redirect = $this->redirectIfContentTypesTableMissing()) {
            return $redirect;
        }

        return parent::index($request);
    }

    public function create()
    {
        if ($redirect = $this->redirectIfContentTypesTableMissing()) {
            return $redirect;
        }

        return parent::create();
    }

    public function store(Request $request)
    {
        if ($redirect = $this->redirectIfContentTypesTableMissing()) {
            return $redirect;
        }

        $this->authorizePermission('create');

        $data = app(ContentTypeRequest::class)->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $contentType = ContentType::create($data);

        app(ContentTypeRegistry::class)->invalidate();

        return redirect()->route('admin.content-types.index')
            ->with('success', "Content type '{$contentType->name}' created successfully.");
    }

    public function update(Request $request, string $id)
    {
        if ($redirect = $this->redirectIfContentTypesTableMissing()) {
            return $redirect;
        }

        $this->authorizePermission('edit');

        $contentType = ContentType::findOrFail($id);

        if ($contentType->is_system) {
            return redirect()->back()->with('error', 'System content types cannot be modified.');
        }

        $data = app(ContentTypeRequest::class)->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $contentType->update($data);
        app(ContentTypeRegistry::class)->invalidate();

        return redirect()->route('admin.content-types.index')
            ->with('success', "Content type '{$contentType->name}' updated successfully.");
    }

    public function destroy(Request $request, string $id)
    {
        if ($redirect = $this->redirectIfContentTypesTableMissing()) {
            return $redirect;
        }

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

    public function fieldBuilder(string $id)
    {
        if ($redirect = $this->redirectIfContentTypesTableMissing()) {
            return $redirect;
        }

        $this->authorizePermission('edit');

        $contentType = ContentType::findOrFail($id);

        return view('admin.content-types.field-builder', compact('contentType'));
    }

    public function saveSchema(Request $request, string $id)
    {
        if ($redirect = $this->redirectIfContentTypesTableMissing()) {
            return $redirect;
        }

        $this->authorizePermission('edit');

        $contentType = ContentType::findOrFail($id);

        $data = $request->validate([
            'fields_schema' => ['nullable', 'array'],
            'fields_schema.*.key' => ['required', 'string', 'max:60', 'regex:/^[a-z_]+$/'],
            'fields_schema.*.label' => ['required', 'string', 'max:100'],
            'fields_schema.*.type' => ['required', 'in:text,textarea,select,checkbox,date,media,number,url,email'],
            'fields_schema.*.required' => ['nullable', 'boolean'],
            'fields_schema.*.options' => ['nullable', 'string'],
        ]);

        $contentType->update(['fields_schema' => $data['fields_schema'] ?? []]);
        app(ContentTypeRegistry::class)->invalidate();

        return redirect()->route('admin.content-types.index')
            ->with('success', "Field schema for '{$contentType->name}' saved.");
    }

    private function redirectIfContentTypesTableMissing()
    {
        if (Schema::hasTable('content_types')) {
            return null;
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('error', 'Content types are unavailable until the latest database migrations are run.');
    }
}
