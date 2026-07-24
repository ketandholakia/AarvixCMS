<?php

namespace App\Http\Controllers\Admin;

use App\AI\Exceptions\AiPromptException;
use App\AI\Services\PromptService;
use App\Http\Controllers\Controller;
use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class AiPromptController extends Controller
{
    public function index()
    {
        $query = $this->buildQuery(request());

        $prompts = $query->paginate(20);
        $summary = $this->buildSummary(clone $query);

        return view('admin.ai-prompts.index', [
            'prompts' => $prompts,
            'summary' => $summary,
            'filters' => [
                'q' => request()->string('q')->toString(),
                'state' => request()->string('state')->toString(),
            ],
        ]);
    }

    public function create()
    {
        $prompt = new AiPrompt([
            'is_enabled' => true,
            'active_version_number' => 1,
        ]);

        $version = [
            'system_template' => '',
            'user_template' => '',
            'variables_json' => '{}',
            'output_schema_json' => '{}',
            'change_summary' => '',
        ];

        return view('admin.ai-prompts.form', [
            'prompt' => $prompt,
            'version' => $version,
            'formMeta' => [
                'mode' => 'create',
                'next_version' => 1,
                'active_version' => 1,
                'version_count' => 0,
            ],
        ]);
    }

    public function import()
    {
        return view('admin.ai-prompts.import');
    }

    public function store(Request $request, PromptService $promptService)
    {
        $data = $this->validatePrompt($request);
        $this->validateVersion($data, $promptService);

        $prompt = AiPrompt::create([
            'prompt_key' => $data['prompt_key'],
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'active_version_number' => 1,
            'output_schema' => $this->decodeJson($data['output_schema_json'] ?? '{}'),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        $prompt->versions()->create([
            'version_number' => 1,
            'system_template' => $data['system_template'],
            'user_template' => $data['user_template'] !== '' ? $data['user_template'] : null,
            'variables' => $this->decodeJson($data['variables_json'] ?? '{}'),
            'output_schema' => $this->decodeJson($data['output_schema_json'] ?? '{}'),
            'change_summary' => $data['change_summary'] ?? null,
        ]);

        return redirect()->route('admin.ai-prompts.show', $prompt)->with('success', 'Prompt created successfully.');
    }

    public function importStore(Request $request, PromptService $promptService): RedirectResponse
    {
        $data = $request->validate([
            'payload_json' => ['nullable', 'string'],
            'payload_file' => ['nullable', 'file', 'max:2048'],
        ]);

        $payloadJson = $this->resolveImportPayloadJson($request, $data);
        $payload = $this->decodeImportPayload($payloadJson);
        $this->validateImportPayload($payload, $promptService);

        $prompt = AiPrompt::create([
            'prompt_key' => $payload['prompt']['prompt_key'],
            'category' => $payload['prompt']['category'],
            'title' => $payload['prompt']['title'],
            'description' => $payload['prompt']['description'] ?? null,
            'active_version_number' => (int) ($payload['prompt']['active_version_number'] ?? 1),
            'output_schema' => $payload['prompt']['output_schema'] ?? [],
            'is_enabled' => (bool) ($payload['prompt']['is_enabled'] ?? false),
        ]);

        foreach ($payload['versions'] as $versionData) {
            $prompt->versions()->create([
                'version_number' => (int) $versionData['version_number'],
                'system_template' => $versionData['system_template'],
                'user_template' => $versionData['user_template'] ?? null,
                'variables' => $versionData['variables'] ?? [],
                'output_schema' => $versionData['output_schema'] ?? [],
                'change_summary' => $versionData['change_summary'] ?? null,
            ]);
        }

        return redirect()->route('admin.ai-prompts.show', $prompt)->with('success', 'Prompt imported successfully.');
    }

    public function export(AiPrompt $ai_prompt)
    {
        $ai_prompt->loadMissing(['versions' => function ($query) {
            $query->orderBy('version_number');
        }]);

        $payload = [
            'prompt' => [
                'prompt_key' => $ai_prompt->prompt_key,
                'category' => $ai_prompt->category,
                'title' => $ai_prompt->title,
                'description' => $ai_prompt->description,
                'active_version_number' => $ai_prompt->active_version_number,
                'output_schema' => $ai_prompt->output_schema,
                'is_enabled' => $ai_prompt->is_enabled,
            ],
            'versions' => $ai_prompt->versions->map(static function (AiPromptVersion $version): array {
                return [
                    'version_number' => $version->version_number,
                    'system_template' => $version->system_template,
                    'user_template' => $version->user_template,
                    'variables' => $version->variables ?? [],
                    'output_schema' => $version->output_schema ?? [],
                    'change_summary' => $version->change_summary,
                ];
            })->values()->all(),
        ];

        return response()->json($payload, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function show(AiPrompt $ai_prompt)
    {
        $ai_prompt->load(['versions' => function ($query) {
            $query->orderByDesc('version_number');
        }]);

        $latestVersion = $ai_prompt->versions->first();

        return view('admin.ai-prompts.show', [
            'prompt' => $ai_prompt,
            'summary' => [
                'active_version' => $ai_prompt->active_version_number,
                'total_versions' => $ai_prompt->versions->count(),
                'state' => $ai_prompt->is_enabled ? 'Enabled' : 'Disabled',
                'latest_version_at' => $latestVersion?->created_at,
            ],
        ]);
    }

    public function edit(AiPrompt $ai_prompt)
    {
        $version = $this->latestVersionPayload($ai_prompt);

        return view('admin.ai-prompts.form', [
            'prompt' => $ai_prompt,
            'version' => $version,
            'formMeta' => [
                'mode' => 'edit',
                'next_version' => ($ai_prompt->versions()->max('version_number') ?? 0) + 1,
                'active_version' => $ai_prompt->active_version_number,
                'version_count' => $ai_prompt->versions()->count(),
            ],
        ]);
    }

    public function update(Request $request, AiPrompt $ai_prompt, PromptService $promptService)
    {
        $data = $this->validatePrompt($request, $ai_prompt);
        $this->validateVersion($data, $promptService);

        $latestVersion = $ai_prompt->versions()->max('version_number') ?? 0;
        $nextVersion = $latestVersion + 1;

        $ai_prompt->update([
            'prompt_key' => $data['prompt_key'],
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'active_version_number' => $nextVersion,
            'output_schema' => $this->decodeJson($data['output_schema_json'] ?? '{}'),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        $ai_prompt->versions()->create([
            'version_number' => $nextVersion,
            'system_template' => $data['system_template'],
            'user_template' => $data['user_template'] !== '' ? $data['user_template'] : null,
            'variables' => $this->decodeJson($data['variables_json'] ?? '{}'),
            'output_schema' => $this->decodeJson($data['output_schema_json'] ?? '{}'),
            'change_summary' => $data['change_summary'] ?? null,
        ]);

        return redirect()->route('admin.ai-prompts.show', $ai_prompt)->with('success', 'Prompt updated successfully.');
    }

    public function destroy(AiPrompt $ai_prompt)
    {
        $ai_prompt->delete();

        return redirect()->route('admin.ai-prompts.index')->with('success', 'Prompt deleted successfully.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:enable,disable'],
            'prompt_ids' => ['required', 'array', 'min:1'],
            'prompt_ids.*' => ['integer', 'distinct', 'exists:ai_prompts,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['prompt_ids'])));
        $isEnabled = $data['action'] === 'enable';
        $updated = AiPrompt::query()
            ->whereIn('id', $ids)
            ->update(['is_enabled' => $isEnabled]);

        $message = $updated . ' prompt' . ($updated === 1 ? '' : 's') . ' ' . ($isEnabled ? 'enabled' : 'disabled') . ' successfully.';

        return redirect()->route('admin.ai-prompts.index')->with('success', $message);
    }

    public function compare(AiPrompt $ai_prompt, AiPromptVersion $version)
    {
        if ((int) $version->ai_prompt_id !== (int) $ai_prompt->id) {
            abort(404);
        }

        $ai_prompt->load(['versions' => function ($query) {
            $query->orderByDesc('version_number');
        }]);

        $activeVersion = $ai_prompt->versions->firstWhere('version_number', $ai_prompt->active_version_number);

        return view('admin.ai-prompts.compare', [
            'prompt' => $ai_prompt,
            'version' => $version,
            'activeVersion' => $activeVersion,
            'comparison' => [
                'system_template_changed' => $activeVersion?->system_template !== $version->system_template,
                'user_template_changed' => $activeVersion?->user_template !== $version->user_template,
                'variables_changed' => json_encode($activeVersion?->variables ?? [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !== json_encode($version->variables ?? [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                'output_schema_changed' => json_encode($activeVersion?->output_schema ?? [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !== json_encode($version->output_schema ?? [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                'change_summary_changed' => $activeVersion?->change_summary !== $version->change_summary,
            ],
        ]);
    }

    public function rollback(AiPrompt $ai_prompt, AiPromptVersion $version)
    {
        if ((int) $version->ai_prompt_id !== (int) $ai_prompt->id) {
            abort(404);
        }

        $ai_prompt->update([
            'active_version_number' => $version->version_number,
        ]);

        return redirect()->route('admin.ai-prompts.show', $ai_prompt)->with('success', "Prompt rolled back to version {$version->version_number}.");
    }

    public function duplicate(AiPrompt $ai_prompt)
    {
        $ai_prompt->loadMissing(['versions' => function ($query) {
            $query->orderByDesc('version_number');
        }]);

        $latestVersion = $ai_prompt->versions->first();

        if ($latestVersion === null) {
            throw new ModelNotFoundException('Unable to clone prompt without versions.');
        }

        $clone = AiPrompt::create([
            'prompt_key' => $this->nextClonePromptKey($ai_prompt->prompt_key),
            'category' => $ai_prompt->category,
            'title' => $ai_prompt->title . ' Copy',
            'description' => $ai_prompt->description,
            'active_version_number' => 1,
            'output_schema' => $ai_prompt->output_schema,
            'is_enabled' => false,
        ]);

        $clone->versions()->create([
            'version_number' => 1,
            'system_template' => $latestVersion->system_template,
            'user_template' => $latestVersion->user_template,
            'variables' => $latestVersion->variables ?? [],
            'output_schema' => $latestVersion->output_schema ?? [],
            'change_summary' => 'Cloned from ' . $ai_prompt->prompt_key . ' version ' . $latestVersion->version_number,
        ]);

        return redirect()
            ->route('admin.ai-prompts.edit', $clone)
            ->with('success', 'Prompt cloned. Review the copy before saving a new version.');
    }

    protected function validatePrompt(Request $request, ?AiPrompt $prompt = null): array
    {
        return $request->validate([
            'prompt_key' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'category' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'system_template' => ['required', 'string'],
            'user_template' => ['nullable', 'string'],
            'variables_json' => ['nullable', 'json'],
            'output_schema_json' => ['nullable', 'json'],
            'change_summary' => ['nullable', 'string', 'max:500'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);
    }

    protected function validateVersion(array $data, PromptService $promptService): void
    {
        try {
            $variables = $this->decodeJson($data['variables_json'] ?? '{}');
            $expected = array_values(array_unique(array_merge(
                $promptService->placeholders($data['system_template']),
                $promptService->placeholders((string) ($data['user_template'] ?? ''))
            )));
            $provided = array_keys($variables);
            sort($expected);
            sort($provided);

            if ($expected !== $provided) {
                $missing = array_values(array_diff($expected, $provided));
                $unknown = array_values(array_diff($provided, $expected));
                $messages = [];

                if ($missing !== []) {
                    $messages[] = 'missing variables: ' . implode(', ', $missing);
                }

                if ($unknown !== []) {
                    $messages[] = 'unknown variables: ' . implode(', ', $unknown);
                }

                throw new AiPromptException(implode('; ', $messages));
            }

            $promptService->renderTemplate($data['system_template'], $promptService->filterVariables($data['system_template'], $variables));

            if (($data['user_template'] ?? '') !== '') {
                $promptService->renderTemplate((string) $data['user_template'], $promptService->filterVariables((string) $data['user_template'], $variables));
            }
        } catch (AiPromptException $e) {
            throw ValidationException::withMessages([
                'system_template' => $e->getMessage(),
            ]);
        }
    }

    protected function decodeJson(string $value): array
    {
        $decoded = json_decode($value ?: '{}', true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'variables_json' => 'The JSON value must decode to an object or array.',
            ]);
        }

        return $decoded;
    }

    protected function latestVersionPayload(AiPrompt $prompt): array
    {
        $version = $prompt->versions()->orderByDesc('version_number')->first();

        return [
            'system_template' => $version?->system_template ?? '',
            'user_template' => $version?->user_template ?? '',
            'variables_json' => json_encode($version?->variables ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'output_schema_json' => json_encode($version?->output_schema ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'change_summary' => $version?->change_summary ?? '',
        ];
    }

    protected function buildSummary($query): array
    {
        $prompts = $query->get();

        return [
            'total_prompts' => $prompts->count(),
            'enabled_count' => $prompts->where('is_enabled', true)->count(),
            'disabled_count' => $prompts->where('is_enabled', false)->count(),
            'total_versions' => $prompts->sum('versions_count'),
        ];
    }

    protected function buildQuery(Request $request)
    {
        return AiPrompt::query()
            ->withCount('versions')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->string('q'));

                $query->where(function ($searchQuery) use ($term): void {
                    $searchQuery->where('prompt_key', 'like', '%' . $term . '%')
                        ->orWhere('title', 'like', '%' . $term . '%')
                        ->orWhere('category', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%');
                });
            })
            ->when($request->filled('state'), function ($query) use ($request): void {
                $state = (string) $request->string('state');

                if ($state === 'enabled') {
                    $query->where('is_enabled', true);
                } elseif ($state === 'disabled') {
                    $query->where('is_enabled', false);
                }
            })
            ->orderBy('category')
            ->orderBy('prompt_key');
    }

    protected function nextClonePromptKey(string $promptKey): string
    {
        $baseKey = $promptKey . '-copy';
        $candidate = $baseKey;
        $suffix = 2;

        while (AiPrompt::query()->where('prompt_key', $candidate)->exists()) {
            $candidate = $baseKey . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function decodeImportPayload(string $payloadJson): array
    {
        $payload = json_decode($payloadJson, true);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'payload_json' => 'The imported JSON must decode to an object.',
            ]);
        }

        return $payload;
    }

    protected function resolveImportPayloadJson(Request $request, array $validated): string
    {
        $file = $request->file('payload_file');

        if ($file instanceof UploadedFile) {
            $content = $file->getRealPath() ? file_get_contents($file->getRealPath()) : false;

            return trim($content !== false ? $content : '');
        }

        return trim((string) ($validated['payload_json'] ?? ''));
    }

    protected function validateImportPayload(array $payload, PromptService $promptService): void
    {
        $prompt = $payload['prompt'] ?? null;
        $versions = $payload['versions'] ?? null;

        if (! is_array($prompt) || ! is_array($versions) || $versions === []) {
            throw ValidationException::withMessages([
                'payload_json' => 'The imported JSON must contain prompt metadata and at least one version.',
            ]);
        }

        foreach (['prompt_key', 'category', 'title'] as $field) {
            if (blank($prompt[$field] ?? null)) {
                throw ValidationException::withMessages([
                    'payload_json' => "The imported JSON is missing prompt.{$field}.",
                ]);
            }
        }

        if (AiPrompt::query()->where('prompt_key', $prompt['prompt_key'])->exists()) {
            throw ValidationException::withMessages([
                'payload_json' => 'A prompt with that key already exists.',
            ]);
        }

        foreach ($versions as $versionData) {
            if (! is_array($versionData)) {
                throw ValidationException::withMessages([
                    'payload_json' => 'Each imported version must be an object.',
                ]);
            }

            $requiredFields = ['version_number', 'system_template'];

            foreach ($requiredFields as $field) {
                if (! array_key_exists($field, $versionData)) {
                    throw ValidationException::withMessages([
                        'payload_json' => "The imported JSON is missing version.{$field}.",
                    ]);
                }
            }

            $variables = is_array($versionData['variables'] ?? null) ? $versionData['variables'] : [];
            $userTemplate = (string) ($versionData['user_template'] ?? '');
            $systemTemplate = (string) $versionData['system_template'];

            $expected = array_values(array_unique(array_merge(
                $promptService->placeholders($systemTemplate),
                $promptService->placeholders($userTemplate)
            )));
            $provided = array_keys($variables);
            sort($expected);
            sort($provided);

            if ($expected !== $provided) {
                $missing = array_values(array_diff($expected, $provided));
                $unknown = array_values(array_diff($provided, $expected));
                $messages = [];

                if ($missing !== []) {
                    $messages[] = 'missing variables: ' . implode(', ', $missing);
                }

                if ($unknown !== []) {
                    $messages[] = 'unknown variables: ' . implode(', ', $unknown);
                }

                if ($messages !== []) {
                    throw ValidationException::withMessages([
                        'payload_json' => 'Version ' . ($versionData['version_number'] ?? '?') . ' has invalid variables: ' . implode('; ', $messages),
                    ]);
                }
            }

            $promptService->renderTemplate($systemTemplate, $promptService->filterVariables($systemTemplate, $variables));

            if ($userTemplate !== '') {
                $promptService->renderTemplate($userTemplate, $promptService->filterVariables($userTemplate, $variables));
            }
        }
    }
}
