<?php

namespace App\Services;

use App\Models\Ticket;
use App\Support\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        private readonly TicketNumberGenerator $numberGenerator,
    ) {}

    /**
     * @param  array<string,mixed>  $data
     * @param  UploadedFile[]  $attachments
     */
    public function create(array $data, ?int $createdBy, array $attachments = []): Ticket
    {
        return DB::transaction(function () use ($data, $createdBy, $attachments) {
            $data['ticket_number'] = $this->numberGenerator->generate();
            $data['created_by'] = $createdBy;
            $data['status'] = $data['status'] ?? TicketStatus::New->value;

            $ticket = Ticket::create($data);

            foreach ($attachments as $file) {
                $ticket->addMedia($file)->toMediaCollection('attachments');
            }

            return $ticket->fresh(['media']);
        });
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);
        return $ticket->fresh();
    }

    /**
     * @param  array<string,mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['season_id'])) {
            $query->where('season_id', $filters['season_id']);
        }
        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }
        if (!empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }
        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }
        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function (Builder $b) use ($q) {
                $b->where('subject', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('ticket_number', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        return $query;
    }
}
