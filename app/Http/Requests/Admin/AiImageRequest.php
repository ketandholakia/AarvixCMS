<?php

namespace App\Http\Requests\Admin;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || ! $user->is_active) {
            return false;
        }

        $enabled = app(SettingService::class)->get('ai.image.enabled', config('ai.image.enabled', true));

        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN) && $user->can('manage_media');
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:2000'],
            'operation' => ['required', Rule::in(['generate', 'edit', 'remove_background', 'upscale', 'resize'])],
            'source_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'resolution' => ['nullable', 'string', 'max:20', 'regex:/^\d+x\d+$/'],
            'seed' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
