<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth is handled by route middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories')->ignore($this->route('category')),
            ],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                // Prevent self-referencing (simple check, doesn't prevent deeper loops)
                function ($attribute, $value, $fail) {
                    if ($this->route('category') && $value == $this->route('category')) {
                        $fail('A category cannot be its own parent.');
                    }
                }
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
