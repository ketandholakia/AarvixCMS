<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiWriterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'context' => ['required', Rule::in(['post', 'page', 'entry'])],
            'operation' => ['required', Rule::in(['rewrite', 'shorten', 'expand', 'summarize', 'grammar', 'seo'])],
            'scope' => ['nullable', Rule::in(['document', 'selection'])],
            'document' => ['required', 'string'],
            'selection' => ['nullable', 'string', 'max:20000'],
            'record_id' => ['nullable', 'integer'],
            'content_type_slug' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'tone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
