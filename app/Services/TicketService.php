<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketNote;
use App\Models\TicketReply;
use App\Support\Enums\ReplyAuthor;
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

    public function transition(Ticket $ticket, TicketStatus $status, ?int $adminId = null): Ticket
    {
        $changes = ['status' => $status];

        match ($status) {
            TicketStatus::Assigned => $changes += [
                'admin_id' => $adminId,
                'assigned_at' => now(),
                'closed_at' => null,
            ],
            TicketStatus::Closed => $changes += [
                'closed_at' => now(),
            ],
            default => $ticket->closed_at !== null
                ? $changes['closed_at'] = null
                : null,
        };

        $ticket->update($changes);
        return $ticket->fresh();
    }

    /**
     * @param  array<string,mixed>  $data
     * @param  UploadedFile[]  $attachments
     */
    public function addReply(Ticket $ticket, array $data, ?int $adminId, array $attachments = []): TicketReply
    {
        return DB::transaction(function () use ($ticket, $data, $adminId, $attachments) {
            $reply = $ticket->replies()->create([
                'description' => $data['description'],
                'replied_by' => $data['replied_by'] ?? ReplyAuthor::Admin->value,
                'admin_id' => $adminId,
                'send_email' => (bool) ($data['send_email'] ?? false),
                'send_whatsapp' => (bool) ($data['send_whatsapp'] ?? false),
                'send_sms' => (bool) ($data['send_sms'] ?? false),
            ]);

            foreach ($attachments as $file) {
                $reply->addMedia($file)->toMediaCollection('attachments');
            }

            if ($ticket->status === TicketStatus::New) {
                $ticket->update(['status' => TicketStatus::InProgress]);
            }

            return $reply->fresh(['media']);
        });
    }

    /**
     * @param  array<string,mixed>  $data
     * @param  UploadedFile[]  $attachments
     */
    public function addNote(Ticket $ticket, array $data, ?int $adminId, array $attachments = []): TicketNote
    {
        return DB::transaction(function () use ($ticket, $data, $adminId, $attachments) {
            $note = $ticket->notes()->create([
                'note' => $data['note'],
                'admin_id' => $adminId,
            ]);

            foreach ($attachments as $file) {
                $note->addMedia($file)->toMediaCollection('attachments');
            }

            return $note->fresh(['media']);
        });
    }

    /**
     * Apply admin-dashboard filters to a tickets query.
     *
     * Supported keys:
     *   - status              string|string[]   one or many TicketStatus values
     *   - admin_id            int               assigned admin
     *   - assigned            'me'|'unassigned' shortcut (requires $actingAdminId when 'me')
     *   - created_by          int               admin who created the ticket
     *   - platform            string            TicketPlatform value
     *   - is_valid            bool
     *   - category_id         int
     *   - include_subcategories bool            walk the category tree from category_id
     *   - sub_category_id     int
     *   - sub_sub_category_id int
     *   - type_id             int
     *   - season_id           int
     *   - zone_id             int
     *   - has_replies         bool
     *   - has_notes           bool
     *   - has_attachments     bool
     *   - q                   string            free-text across subject/description/ticket_number,
     *                                           first/last name, email, phone
     *   - created_from / created_to             ISO dates on created_at
     *   - occurrence_from / occurrence_to       ISO dates on occurrence_time
     *   - closed_from / closed_to               ISO dates on closed_at
     *   - sort                'latest'|'oldest'|'updated'|'closed'
     *   - with_trashed        bool              include soft-deleted
     *   - only_trashed        bool              only soft-deleted
     *
     * @param  array<string,mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters, ?int $actingAdminId = null): Builder
    {
        // --- status (single or multi) -----------------------------------
        if (!empty($filters['status'])) {
            $statuses = array_values(array_filter(
                is_array($filters['status']) ? $filters['status'] : [$filters['status']],
                fn ($s) => TicketStatus::tryFrom((string) $s) !== null,
            ));
            if ($statuses !== []) {
                $query->whereIn('status', $statuses);
            }
        }

        // --- assignment ------------------------------------------------
        if (($filters['assigned'] ?? null) === 'unassigned') {
            $query->whereNull('admin_id');
        } elseif (($filters['assigned'] ?? null) === 'me' && $actingAdminId) {
            $query->where('admin_id', $actingAdminId);
        } elseif (!empty($filters['admin_id'])) {
            $query->where('admin_id', (int) $filters['admin_id']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', (int) $filters['created_by']);
        }

        // --- platform / validity ---------------------------------------
        if (!empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }
        if (array_key_exists('is_valid', $filters) && $filters['is_valid'] !== null && $filters['is_valid'] !== '') {
            $query->where('is_valid', (bool) $filters['is_valid']);
        }

        // --- taxonomy --------------------------------------------------
        if (!empty($filters['category_id'])) {
            $categoryId = (int) $filters['category_id'];
            if (!empty($filters['include_subcategories'])) {
                $query->whereIn('category_id', $this->descendantCategoryIds($categoryId));
            } else {
                $query->where('category_id', $categoryId);
            }
        }
        foreach (['sub_category_id', 'sub_sub_category_id', 'type_id', 'season_id', 'zone_id'] as $key) {
            if (!empty($filters[$key])) {
                $query->where($key, (int) $filters[$key]);
            }
        }

        // --- relation existence ----------------------------------------
        if (!empty($filters['has_replies'])) {
            $query->whereHas('replies');
        }
        if (!empty($filters['has_notes'])) {
            $query->whereHas('notes');
        }
        if (!empty($filters['has_attachments'])) {
            $query->whereHas('media', fn (Builder $b) => $b->where('collection_name', 'attachments'));
        }

        // --- free-text search ------------------------------------------
        if (!empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->where(function (Builder $b) use ($q) {
                $b->where('subject', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('ticket_number', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        // --- date ranges -----------------------------------------------
        foreach ([
            'created_at'      => ['created_from',    'created_to'],
            'occurrence_time' => ['occurrence_from', 'occurrence_to'],
            'closed_at'       => ['closed_from',     'closed_to'],
        ] as $column => [$fromKey, $toKey]) {
            if (!empty($filters[$fromKey])) {
                $query->where($column, '>=', $filters[$fromKey]);
            }
            if (!empty($filters[$toKey])) {
                $query->where($column, '<=', $filters[$toKey]);
            }
        }

        // Backwards-compat: old `from`/`to` keys still hit created_at.
        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        // --- soft delete scope -----------------------------------------
        if (!empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        } elseif (!empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        // --- sort ------------------------------------------------------
        $this->applySort($query, $filters['sort'] ?? 'latest');

        return $query;
    }

    private function applySort(Builder $query, string $sort): void
    {
        $query->getQuery()->orders = null;

        match ($sort) {
            'oldest'  => $query->oldest('id'),
            'updated' => $query->latest('updated_at'),
            'closed'  => $query->orderByDesc('closed_at')->orderByDesc('id'),
            default   => $query->latest('id'),
        };
    }

    /**
     * Collect the given category id plus every descendant id (any depth).
     *
     * @return int[]
     */
    private function descendantCategoryIds(int $rootId): array
    {
        $ids = [$rootId];
        $frontier = [$rootId];

        while ($frontier !== []) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            if ($children === []) {
                break;
            }

            $ids = array_merge($ids, $children);
            $frontier = $children;
        }

        return $ids;
    }
}
