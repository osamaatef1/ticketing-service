<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreZoneRequest;
use App\Http\Requests\UpdateZoneRequest;
use App\Http\Resources\ZoneResource;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ZoneController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Zone::query()->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }
        if ($request->filled('season_id')) {
            $query->where('season_id', $request->integer('season_id'));
        }
        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where('name', 'like', "%{$q}%");
        }
        if ($request->boolean('with_season')) {
            $query->with('season');
        }
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        $perPage = max(1, min((int) $request->query('per_page', 20), 100));

        return ZoneResource::collection($query->paginate($perPage));
    }

    public function store(StoreZoneRequest $request): JsonResponse
    {
        $zone = Zone::create($request->validated());

        return ZoneResource::make($zone->fresh())
            ->response()
            ->setStatusCode(201);
    }

    public function show(Zone $zone): ZoneResource
    {
        return ZoneResource::make($zone->load('season'));
    }

    public function update(UpdateZoneRequest $request, Zone $zone): ZoneResource
    {
        $zone->update($request->validated());
        return ZoneResource::make($zone->fresh());
    }

    public function destroy(Zone $zone): JsonResponse
    {
        $zone->delete();
        return response()->json(['message' => 'deleted']);
    }
}
