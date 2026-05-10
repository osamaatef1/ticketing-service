<?php

namespace App\Http\Requests;

use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreZoneRequest extends FormRequest
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
            'season_id' => ['required', 'integer', Rule::exists(Season::class, 'id')],
        ];
    }
}
