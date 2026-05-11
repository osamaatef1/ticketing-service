<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReplyRequest;
use App\Http\Resources\TicketReplyResource;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\ActingAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketReplyController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(Ticket $ticket): AnonymousResourceCollection
    {
        $replies = $ticket->replies()
            ->with('media')
            ->latest('id')
            ->paginate(20);

        return TicketReplyResource::collection($replies);
    }

    public function store(StoreReplyRequest $request, Ticket $ticket): JsonResponse
    {
        $admin = app(ActingAdmin::class);
        $files = $request->file('attachments') ?? [];

        $reply = $this->tickets->addReply(
            $ticket,
            $request->safe()->except('attachments'),
            $admin->id,
            $files
        );

        return TicketReplyResource::make($reply->load('media'))
            ->response()
            ->setStatusCode(201);
    }
}
