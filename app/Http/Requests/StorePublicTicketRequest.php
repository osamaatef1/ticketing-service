<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Season;
use App\Models\Zone;
use App\Support\Enums\TicketPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],

            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'country_code' => ['nullable', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:32', 'required_without:email'],

            'platform' => ['nullable', Rule::in([TicketPlatform::Web->value, TicketPlatform::WhatsApp->value])],
            'occurrence_time' => ['nullable', 'date'],

            'category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'season_id' => ['nullable', 'integer', Rule::exists(Season::class, 'id')],
            'zone_id' => ['nullable', 'integer', Rule::exists(Zone::class, 'id')],

            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Provide either an email or a phone so we can reach you.',
            'phone.required_without' => 'Provide either an email or a phone so we can reach you.',
        ];
    }
}
