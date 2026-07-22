<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ContentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $contentType = $this->route('content_type');
        $uniqueSlug = 'unique:content_types,slug' . ($contentType ? ",{$contentType}" : '');

        return [
            'name'          => ['required', 'string', 'max:100'],
            'slug'          => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', $uniqueSlug],
            'context'       => ['required', 'in:post,page'],
            'icon'          => ['nullable', 'string', 'max:50'],
            'description'   => ['nullable', 'string', 'max:500'],
            'is_active'     => ['boolean'],
            'fields_schema' => ['nullable', 'array'],
            'fields_schema.*.key'      => ['required_with:fields_schema', 'string', 'max:60', 'regex:/^[a-z_]+$/'],
            'fields_schema.*.label'    => ['required_with:fields_schema', 'string', 'max:100'],
            'fields_schema.*.type'     => ['required_with:fields_schema', 'in:text,textarea,select,checkbox,date,media,number,url,email'],
            'fields_schema.*.required' => ['boolean'],
            'fields_schema.*.options'  => ['nullable', 'string'], // comma-separated, for select type
        ];
    }
}
