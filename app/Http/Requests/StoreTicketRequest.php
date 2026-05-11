<?php

namespace App\Http\Requests;

use App\Support\Enums\TicketPlatform;
use App\Support\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:32'],

            'status' => ['nullable', new Enum(TicketStatus::class)],
            'platform' => ['nullable', new Enum(TicketPlatform::class)],
            'occurrence_time' => ['nullable', 'date'],
            'is_valid' => ['nullable', 'boolean'],

            'admin_id' => ['nullable', 'integer'],
            'ticket_category_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'sub_category_id' => ['nullable', 'integer'],
            'sub_sub_category_id' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer'],
            'season_id' => ['nullable', 'integer'],
            'zone_id' => ['nullable', 'integer'],

            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
        ];
    }
}
