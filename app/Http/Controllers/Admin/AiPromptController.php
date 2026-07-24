<?php

namespace App\Http\Controllers\Admin;

use App\AI\Exceptions\AiPromptException;
use App\AI\Services\PromptService;
use App\Http\Controllers\Controller;
use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiPromptController extends Controller
{
    public function index()
    {
        $query = AiPrompt::query()
            ->withCount('versions')
            ->orderBy('category')
            ->orderBy('prompt_key');

        $prompts = $query->paginate(20);
        $summary = $this->buildSummary(clone $query);

        return view('admin.ai-prompts.index', compact('prompts', 'summary'));
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

        return view('admin.ai-prompts.form', compact('prompt', 'version'));
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

    public function show(AiPrompt $ai_prompt)
    {
        $ai_prompt->load(['versions' => function ($query) {
            $query->orderByDesc('version_number');
        }]);

        return view('admin.ai-prompts.show', [
            'prompt' => $ai_prompt,
        ]);
    }

    public function edit(AiPrompt $ai_prompt)
    {
        $version = $this->latestVersionPayload($ai_prompt);

        return view('admin.ai-prompts.form', [
            'prompt' => $ai_prompt,
            'version' => $version,
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

    public function compare(AiPrompt $ai_prompt, AiPromptVersion $version)
    {
        if ((int) $version->ai_prompt_id !== (int) $ai_prompt->id) {
            abort(404);
        }

        $ai_prompt->load(['versions' => function ($query) {
            $query->orderByDesc('version_number');
        }]);

        return view('admin.ai-prompts.compare', [
            'prompt' => $ai_prompt,
            'version' => $version,
            'activeVersion' => $ai_prompt->versions->firstWhere('version_number', $ai_prompt->active_version_number),
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
}
