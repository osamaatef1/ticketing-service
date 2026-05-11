<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeasonRequest;
use App\Http\Requests\UpdateSeasonRequest;
use App\Http\Resources\SeasonResource;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SeasonController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Season::query()->with('media')->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }
        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where('name', 'like', "%{$q}%");
        }
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        $perPage = max(1, min((int) $request->query('per_page', 20), 100));

        return SeasonResource::collection($query->paginate($perPage));
    }

    public function store(StoreSeasonRequest $request): JsonResponse
    {
        $season = Season::create($request->safe()->except('cover'));

        if ($request->hasFile('cover')) {
            $season->addMedia($request->file('cover'))->toMediaCollection('cover');
        }

        return SeasonResource::make($season->fresh()->load('media'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Season $season): SeasonResource
    {
        return SeasonResource::make($season->load('media'));
    }

    public function update(UpdateSeasonRequest $request, Season $season): SeasonResource
    {
        $season->update($request->safe()->except(['cover', 'remove_cover']));

        if ($request->boolean('remove_cover')) {
            $season->clearMediaCollection('cover');
        }
        if ($request->hasFile('cover')) {
            $season->addMedia($request->file('cover'))->toMediaCollection('cover');
        }

        return SeasonResource::make($season->fresh()->load('media'));
    }

    public function destroy(Season $season): JsonResponse
    {
        $season->delete();
        return response()->json(['message' => 'deleted']);
    }
}
