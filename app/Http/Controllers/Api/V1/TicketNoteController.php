<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Resources\TicketNoteResource;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\ActingAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketNoteController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(Ticket $ticket): AnonymousResourceCollection
    {
        $notes = $ticket->notes()
            ->with('media')
            ->latest('id')
            ->paginate(20);

        return TicketNoteResource::collection($notes);
    }

    public function store(StoreNoteRequest $request, Ticket $ticket): JsonResponse
    {
        $admin = app(ActingAdmin::class);
        $files = $request->file('attachments') ?? [];

        $note = $this->tickets->addNote(
            $ticket,
            $request->safe()->except('attachments'),
            $admin->id,
            $files
        );

        return TicketNoteResource::make($note->load('media'))
            ->response()
            ->setStatusCode(201);
    }
}
