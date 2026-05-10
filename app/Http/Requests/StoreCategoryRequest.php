<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.en' => ['required_without:name.ar', 'string', 'max:255'],
            'name.ar' => ['required_without:name.en', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'is_custom' => ['nullable', 'boolean'],
            'enable_email' => ['nullable', 'boolean'],
            'zone_id' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
        ];
    }
}
