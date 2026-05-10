<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeasonRequest extends FormRequest
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
            'cover' => ['nullable', 'file', 'image', 'max:20480'],
        ];
    }
}
