<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\ActingAdmin;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ticket::query()
            ->withCount(['replies', 'notes'])
            ->with('media');

        $filters = $request->only([
            // identity
            'status', 'admin_id', 'assigned', 'created_by',
            // attributes
            'platform', 'is_valid',
            // taxonomy
            'category_id', 'include_subcategories',
            'sub_category_id', 'sub_sub_category_id',
            'type_id', 'season_id', 'zone_id',
            // relation existence
            'has_replies', 'has_notes', 'has_attachments',
            // search + date ranges
            'q',
            'created_from', 'created_to',
            'occurrence_from', 'occurrence_to',
            'closed_from', 'closed_to',
            'from', 'to',
            // scope + sort
            'with_trashed', 'only_trashed', 'sort',
        ]);

        $actingAdmin = Container::getInstance()->bound(ActingAdmin::class)
            ? Container::getInstance()->make(ActingAdmin::class)
            : null;

        $this->tickets->applyFilters($query, $filters, $actingAdmin?->id);

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        return TicketResource::collection($query->paginate($perPage)->appends($request->query()));
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $admin = app(ActingAdmin::class);
        $data = $request->safe()->except('attachments');
        $files = $request->file('attachments') ?? [];

        $ticket = $this->tickets->create($data, $admin->id, $files);

        return TicketResource::make($ticket->loadCount(['replies', 'notes']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketResource
    {
        $ticket->load(['replies.media', 'notes.media', 'media'])
            ->loadCount(['replies', 'notes']);

        return TicketResource::make($ticket);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->tickets->update($ticket, $request->validated());
        return TicketResource::make($ticket->loadCount(['replies', 'notes'])->load('media'));
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
        $ticket->delete();
        return response()->json(['message' => 'deleted']);
    }
}
