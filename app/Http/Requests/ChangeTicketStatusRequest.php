<?php

namespace App\Http\Requests;

use App\Support\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ChangeTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(TicketStatus::class)],
            'admin_id' => [
                Rule::requiredIf(fn () => $this->input('status') === TicketStatus::Assigned->value),
                'integer',
                'min:1',
            ],
        ];
    }
}
