<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['sometimes', 'array'],
            'name.en' => ['sometimes', 'string', 'max:255'],
            'name.ar' => ['sometimes', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'is_custom' => ['nullable', 'boolean'],
            'enable_email' => ['nullable', 'boolean'],
            'zone_id' => ['nullable', 'integer', 'min:1'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(Category::class, 'id'),
                Rule::notIn([$categoryId]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ];
    }
}
