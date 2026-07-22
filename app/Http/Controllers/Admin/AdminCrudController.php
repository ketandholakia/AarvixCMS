<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class AdminCrudController extends Controller
{
    /**
     * Get the model class this controller manages.
     * e.g., \App\Models\Post::class
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the view path prefix for this resource.
     * e.g., 'admin.posts'
     */
    abstract protected function getViewPrefix(): string;

    /**
     * Get the route prefix for redirects.
     * e.g., 'admin.posts'
     */
    abstract protected function getRoutePrefix(): string;

    /**
     * Get the form request class for validation.
     * Return null to use basic $this->getValidationRules() instead.
     */
    protected function getFormRequestClass(): ?string
    {
        return null;
    }

    /**
     * Fallback validation rules if no FormRequest is provided.
     */
    protected function getValidationRules(Request $request, ?Model $model = null): array
    {
        return [];
    }

    /**
     * Define searchable columns for the index view.
     */
    protected function getSearchableColumns(): array
    {
        return ['name', 'title'];
    }

    // --- Authorization ---

    /**
     * The resource name used to build permission strings, e.g. 'posts' for
     * 'view_posts' / 'create_posts' / 'edit_posts' / 'delete_posts'.
     * Defaults to the last segment of the route prefix (admin.posts -> posts).
     * Override in a subclass whose seeded permissions don't follow that pattern
     * (e.g. Roles only have view_roles/edit_roles, not create_/delete_).
     */
    protected function permissionMap(): array
    {
        $resource = str($this->getRoutePrefix())->afterLast('.')->toString();

        return [
            'view'   => "view_{$resource}",
            'create' => "create_{$resource}",
            'edit'   => "edit_{$resource}",
            'delete' => "delete_{$resource}",
        ];
    }

    /**
     * Abort with 403 unless the current user holds the given permission.
     */
    protected function authorizePermission(string $action): void
    {
        $permission = $this->permissionMap()[$action] ?? null;

        // No permission mapped for this action = deliberately open (should be rare;
        // prefer mapping an explicit permission over leaving this empty).
        if ($permission === null) {
            return;
        }

        if (! auth()->user()?->hasPermission($permission)) {
            abort(403, 'You do not have the required permissions.');
        }
    }

    /**
     * Hook for per-record ownership/authorship rules (e.g. an Author may only
     * edit their own posts even though they hold 'edit_posts'). No-op by default;
     * override in subclasses backed by a Policy (see PostController, PageController).
     */
    protected function authorizeOwnership(string $ability, Model $model): void
    {
        //
    }

    /**
     * Hook to modify the query before pagination.
     */
    protected function indexQuery($query)
    {
        return $query->latest();
    }

    // --- Lifecycle Hooks ---

    protected function beforeStore(Request $request, array &$data): void {}
    protected function afterStore(Request $request, Model $model): void {}

    protected function beforeUpdate(Request $request, Model $model, array &$data): void {}
    protected function afterUpdate(Request $request, Model $model): void {}

    protected function beforeDestroy(Request $request, Model $model): void {}
    
    // --- CRUD Methods ---

    public function index(Request $request)
    {
        $this->authorizePermission('view');

        $modelClass = $this->getModelClass();

        $query = $modelClass::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                foreach ($this->getSearchableColumns() as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $query = $this->indexQuery($query);
        $records = $query->paginate(20)->withQueryString();

        return view($this->getViewPrefix() . '.index', compact('records'));
    }

    public function create()
    {
        $this->authorizePermission('create');

        $modelClass = $this->getModelClass();
        $record = new $modelClass;

        return view($this->getViewPrefix() . '.form', compact('record'));
    }

    public function store(Request $request)
    {
        $this->authorizePermission('create');

        if ($formRequest = $this->getFormRequestClass()) {
            $request = app($formRequest);
            $data = $request->validated();
        } else {
            $data = $request->validate($this->getValidationRules($request));
        }

        $this->beforeStore($request, $data);

        $modelClass = $this->getModelClass();
        $model = $modelClass::create($data);

        $this->afterStore($request, $model);

        app(ActivityService::class)->log(auth()->user(), 'created', $model);

        return redirect()->route($this->getRoutePrefix() . '.index')
            ->with('success', class_basename($modelClass) . ' created successfully.');
    }

    public function edit(string $id)
    {
        $this->authorizePermission('edit');

        $modelClass = $this->getModelClass();
        $record = $modelClass::findOrFail($id);
        $this->authorizeOwnership('update', $record);

        return view($this->getViewPrefix() . '.form', compact('record'));
    }

    public function update(Request $request, string $id)
    {
        $this->authorizePermission('edit');

        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);
        $this->authorizeOwnership('update', $model);

        if ($formRequest = $this->getFormRequestClass()) {
            $request = app($formRequest);
            $data = $request->validated();
        } else {
            $data = $request->validate($this->getValidationRules($request, $model));
        }

        $this->beforeUpdate($request, $model, $data);

        $model->update($data);

        $this->afterUpdate($request, $model);

        app(ActivityService::class)->log(auth()->user(), 'updated', $model);

        return redirect()->route($this->getRoutePrefix() . '.index')
            ->with('success', class_basename($modelClass) . ' updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizePermission('delete');

        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);
        $this->authorizeOwnership('delete', $model);

        $this->beforeDestroy($request, $model);

        app(ActivityService::class)->log(auth()->user(), 'deleted', $model);

        $model->delete();

        return redirect()->route($this->getRoutePrefix() . '.index')
            ->with('success', class_basename($modelClass) . ' deleted successfully.');
    }
}
