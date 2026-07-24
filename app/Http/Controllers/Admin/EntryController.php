<?php

namespace App\Http\Controllers\Admin;

use App\Models\Entry;
use App\Models\AiRequest;
use App\Models\ContentType;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Media;
use App\Services\ContentTypeRegistry;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;

class EntryController extends Controller
{
    protected ContentType $contentType;

    /**
     * Resolve and cache the current ContentType from the route parameter.
     */
    protected function resolveContentType(Request $request): ContentType
    {
        if (!isset($this->contentType)) {
            $typeSlug = $request->route('type');
            $this->contentType = app(ContentTypeRegistry::class)->find($typeSlug)
                ?? abort(404, "Content type '{$typeSlug}' not found.");
        }

        return $this->contentType;
    }


    protected function authorizePermission(string $action): void
    {
        // Build permission from the type slug (e.g., 'create_portfolio')
        $slug = request()->route('type') ?? 'entries';
        $map = [
            'view'   => "view_{$slug}",
            'create' => "create_{$slug}",
            'edit'   => "edit_{$slug}",
            'delete' => "delete_{$slug}",
        ];

        $permission = $map[$action] ?? null;
        if ($permission && !auth()->user()?->hasPermission($permission)) {
            abort(403, 'You do not have the required permissions.');
        }
    }



    // ─── CRUD Overrides ────────────────────────────────────────────────────────

    public function index(Request $request, string $type)
    {
        $contentType = $this->resolveContentType($request);
        $this->authorizePermission('view');

        $query = Entry::where('content_type_id', $this->contentType->id)
            ->with(['author', 'category'])
            ->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $records = $query->paginate(20)->withQueryString();

        return view('admin.entries.index', compact('records', 'contentType'));
    }

    public function create(Request $request, string $type)
    {
        $contentType = $this->resolveContentType($request);
        $this->authorizePermission('create');

        $record = new Entry(['content_type_id' => $contentType->id]);
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.entries.form', compact('record', 'contentType', 'categories', 'tags'));
    }

    public function store(Request $request, string $type)
    {
        $contentType = $this->resolveContentType($request);
        $this->authorizePermission('create');
        $contextRequest = $request;

        $data = $this->validateEntry($request, $contentType);
        $data['content_type_id'] = $contentType->id;
        $data['author_id'] = auth()->id();

        $entry = new Entry();
        $entry->fill($data);
        $this->applyRevisionContext($contextRequest, $entry);
        $entry->save();

        // Sync tags
        if ($request->has('tags')) {
            $entry->tags()->sync($request->input('tags', []));
        }

        app(ActivityService::class)->log(auth()->user(), 'created', $entry);

        return redirect()->route('admin.entries.index', ['type' => $contentType->slug])
            ->with('success', "'{$entry->title}' created successfully.");
    }

    public function edit(Request $request, string $type, string $id)
    {
        $contentType = $this->resolveContentType($request);
        $this->authorizePermission('edit');

        $record = Entry::where('content_type_id', $contentType->id)->findOrFail($id);
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.entries.form', compact('record', 'contentType', 'categories', 'tags'));
    }

    public function update(Request $request, string $type, string $id)
    {
        $contentType = $this->resolveContentType($request);
        $this->authorizePermission('edit');
        $contextRequest = $request;

        $entry = Entry::where('content_type_id', $contentType->id)->findOrFail($id);
        $data = $this->validateEntry($request, $contentType, $entry);

        $entry->fill($data);
        $this->applyRevisionContext($contextRequest, $entry);
        $entry->save();

        if ($request->has('tags')) {
            $entry->tags()->sync($request->input('tags', []));
        }

        app(ActivityService::class)->log(auth()->user(), 'updated', $entry);

        return redirect()->route('admin.entries.index', ['type' => $contentType->slug])
            ->with('success', "'{$entry->title}' updated successfully.");
    }

    public function destroy(Request $request, string $type, string $id)
    {
        $contentType = $this->resolveContentType($request);
        $this->authorizePermission('delete');

        $entry = Entry::where('content_type_id', $contentType->id)->findOrFail($id);

        app(ActivityService::class)->log(auth()->user(), 'deleted', $entry);
        $entry->delete();

        return redirect()->route('admin.entries.index', ['type' => $contentType->slug])
            ->with('success', 'Entry deleted successfully.');
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    private function validateEntry(Request $request, ContentType $contentType, ?Entry $entry = null): array
    {
        $uniqueSlug = 'unique:entries,slug,NULL,id,content_type_id,' . $contentType->id;
        if ($entry) {
            $uniqueSlug = "unique:entries,slug,{$entry->id},id,content_type_id,{$contentType->id}";
        }

        $rules = [
            'title'              => ['required', 'string', 'max:255'],
            'slug'               => ['nullable', 'string', 'max:255', $uniqueSlug],
            'body'               => ['nullable', 'string'],
            'excerpt'            => ['nullable', 'string', 'max:500'],
            'status'             => ['required', 'in:draft,published,archived'],
            'published_at'       => ['nullable', 'date'],
            'category_id'        => ['nullable', 'exists:categories,id'],
            'featured_image_id'  => ['nullable', 'exists:media,id'],
            'meta_title'         => ['nullable', 'string', 'max:255'],
            'meta_description'   => ['nullable', 'string', 'max:500'],
            'template'           => ['nullable', 'string', 'max:50'],
            'tags'               => ['nullable', 'array'],
            'tags.*'             => ['exists:tags,id'],
            'custom_fields'      => ['nullable', 'array'],
        ];

        // Add validation rules for each custom field in the schema
        foreach ($contentType->fieldDefinitions() as $field) {
            $key = 'custom_fields.' . $field['key'];
            $fieldRules = ['nullable'];

            if (!empty($field['required'])) {
                $fieldRules = ['required'];
            }

            match ($field['type']) {
                'url'      => $fieldRules[] = 'url',
                'email'    => $fieldRules[] = 'email',
                'number'   => $fieldRules[] = 'numeric',
                'date'     => $fieldRules[] = 'date',
                'checkbox' => $fieldRules[] = 'boolean',
                default    => $fieldRules[] = 'string',
            };

            $rules[$key] = $fieldRules;
        }

        return $request->validate($rules);
    }

    protected function applyRevisionContext(Request $request, Model $model): void
    {
        if (! method_exists($model, 'withRevisionContext')) {
            return;
        }

        $aiRequestId = $this->resolveAiRequestId($request);

        if ($aiRequestId !== null) {
            $model->withRevisionContext($aiRequestId);
        }
    }

    protected function resolveAiRequestId(Request $request): ?int
    {
        $requestUuid = $request->input('ai_request_uuid');

        if (! is_string($requestUuid) || trim($requestUuid) === '') {
            return null;
        }

        $aiRequestId = AiRequest::query()
            ->where('request_uuid', trim($requestUuid))
            ->value('id');

        return is_int($aiRequestId) && $aiRequestId > 0 ? $aiRequestId : null;
    }
}
