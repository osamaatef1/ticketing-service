<?php

namespace App\Http\Requests;

use App\Support\Enums\ReplyAuthor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'replied_by' => ['nullable', new Enum(ReplyAuthor::class)],
            'send_email' => ['nullable', 'boolean'],
            'send_whatsapp' => ['nullable', 'boolean'],
            'send_sms' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
        ];
    }
}
