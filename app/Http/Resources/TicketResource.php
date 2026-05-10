<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use App\Services\AdminClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $admins = $this->resolveAdmins();

        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'sequence_number' => $this->sequence_number,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status?->value,
            'platform' => $this->platform?->value,
            'token' => $this->token,
            'is_valid' => $this->is_valid,
            'occurrence_time' => $this->occurrence_time?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),

            'customer' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'country_code' => $this->country_code,
                'phone' => $this->phone,
            ],

            'taxonomy' => [
                'ticket_category_id' => $this->ticket_category_id,
                'category_id' => $this->category_id,
                'sub_category_id' => $this->sub_category_id,
                'sub_sub_category_id' => $this->sub_sub_category_id,
                'type_id' => $this->type_id,
                'season_id' => $this->season_id,
                'zone_id' => $this->zone_id,
            ],

            'created_by' => $this->adminPayload($this->created_by, $admins),
            'assigned_admin' => $this->adminPayload($this->admin_id, $admins),

            'attachments' => MediaResource::collection($this->whenLoaded('media')),
            'replies_count' => $this->whenCounted('replies'),
            'notes_count' => $this->whenCounted('notes'),

            'replies' => TicketReplyResource::collection($this->whenLoaded('replies')),
            'notes' => TicketNoteResource::collection($this->whenLoaded('notes')),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<int, array{id:int,name:?string,email:?string,avatar_url:?string}> */
    private function resolveAdmins(): array
    {
        $ids = array_filter([$this->created_by, $this->admin_id]);
        if ($ids === []) {
            return [];
        }
        $dtos = app(AdminClient::class)->findMany($ids);
        $out = [];
        foreach ($dtos as $id => $dto) {
            $out[$id] = $dto->toArray();
        }
        return $out;
    }

    private function adminPayload(?int $id, array $admins): ?array
    {
        if (!$id) {
            return null;
        }
        return $admins[$id] ?? ['id' => $id, 'name' => null, 'email' => null, 'avatar_url' => null, '_stale' => true];
    }
}
