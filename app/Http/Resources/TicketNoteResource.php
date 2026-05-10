<?php

namespace App\Http\Resources;

use App\Models\TicketNote;
use App\Services\AdminClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketNote */
class TicketNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $admin = $this->admin_id
            ? app(AdminClient::class)->find($this->admin_id)
            : null;

        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'note' => $this->note,
            'admin' => $admin
                ? $admin->toArray()
                : ($this->admin_id ? ['id' => $this->admin_id, 'name' => null, '_stale' => true] : null),
            'attachments' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
