<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\Enums\TicketStatus;

class TicketStatusController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function __invoke(ChangeTicketStatusRequest $request, Ticket $ticket): TicketResource
    {
        $status = TicketStatus::from($request->validated('status'));
        $adminId = $request->integer('admin_id') ?: null;

        $ticket = $this->tickets->transition($ticket, $status, $adminId);

        return TicketResource::make(
            $ticket->load('media')->loadCount(['replies', 'notes'])
        );
    }
}
