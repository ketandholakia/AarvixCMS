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
        $modelClass = $this->getModelClass();
        
        // Authorization check if policy exists (handled by middleware or manual auth here)
        // We assume policy is enforced via route middleware, but can be added here if needed.

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
        $modelClass = $this->getModelClass();
        $record = new $modelClass;

        return view($this->getViewPrefix() . '.form', compact('record'));
    }

    public function store(Request $request)
    {
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
        $modelClass = $this->getModelClass();
        $record = $modelClass::findOrFail($id);

        return view($this->getViewPrefix() . '.form', compact('record'));
    }

    public function update(Request $request, string $id)
    {
        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);

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
        $modelClass = $this->getModelClass();
        $model = $modelClass::findOrFail($id);

        $this->beforeDestroy($request, $model);

        app(ActivityService::class)->log(auth()->user(), 'deleted', $model);

        $model->delete();

        return redirect()->route($this->getRoutePrefix() . '.index')
            ->with('success', class_basename($modelClass) . ' deleted successfully.');
    }
}
