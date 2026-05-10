<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketNumberGenerator
{
    public function generate(): string
    {
        $year = date('Y');
        $prefix = "TKT-{$year}-";

        return DB::transaction(function () use ($prefix, $year) {
            $last = Ticket::withTrashed()
                ->where('ticket_number', 'like', $prefix.'%')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('ticket_number');

            $next = $last
                ? ((int) substr($last, strlen($prefix))) + 1
                : 1;

            return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        });
    }
}
