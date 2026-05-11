<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Category::query()->withCount('children')->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->integer('zone_id'));
        }
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        } elseif ($request->boolean('roots_only')) {
            $query->whereNull('parent_id');
        }
        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where('name', 'like', "%{$q}%");
        }
        if ($request->boolean('with_children')) {
            $query->with('children');
        }
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        $perPage = max(1, min((int) $request->query('per_page', 20), 100));

        return CategoryResource::collection($query->paginate($perPage));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return CategoryResource::make($category->fresh()->loadCount('children'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        return CategoryResource::make(
            $category->load('children')->loadCount('children')
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());
        return CategoryResource::make($category->fresh()->loadCount('children'));
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['message' => 'deleted']);
    }
}
