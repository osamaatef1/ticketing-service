<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicTicketRequest;
use App\Http\Resources\TicketResource;
use App\Services\TicketService;
use App\Support\Enums\TicketPlatform;
use App\Support\Enums\TicketStatus;
use Illuminate\Http\JsonResponse;

class PublicTicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function store(StorePublicTicketRequest $request): JsonResponse
    {
        $data = $request->safe()->except('attachments');

        $data['platform'] = $data['platform'] ?? TicketPlatform::Web->value;
        $data['status'] = TicketStatus::New->value;

        $ticket = $this->tickets->create(
            $data,
            createdBy: null,
            attachments: $request->file('attachments') ?? []
        );

        return TicketResource::make($ticket->loadCount(['replies', 'notes']))
            ->response()
            ->setStatusCode(201);
    }
}
