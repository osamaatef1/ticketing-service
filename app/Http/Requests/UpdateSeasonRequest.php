<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'array'],
            'name.en' => ['sometimes', 'string', 'max:255'],
            'name.ar' => ['sometimes', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'file', 'image', 'max:20480'],
            'remove_cover' => ['nullable', 'boolean'],
        ];
    }
}
