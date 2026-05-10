<?php

namespace App\Http\Requests;

use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateZoneRequest extends FormRequest
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
            'season_id' => ['sometimes', 'integer', Rule::exists(Season::class, 'id')],
        ];
    }
}
