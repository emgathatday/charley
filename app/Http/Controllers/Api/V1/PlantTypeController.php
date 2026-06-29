<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlantTypeRequest;
use App\Http\Resources\PlantTypeResource;
use App\Models\PlantType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PlantTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PlantType::query()->sorted();

        if ($request->user()->role !== 'admin' || ! $request->boolean('include_inactive')) {
            $query->active();
        }

        return PlantTypeResource::collection($query->paginate(50));
    }

    public function store(PlantTypeRequest $request): PlantTypeResource
    {
        return PlantTypeResource::make(PlantType::query()->create($request->validated()));
    }

    public function show(PlantType $plantType): PlantTypeResource
    {
        return PlantTypeResource::make($plantType);
    }

    public function update(PlantTypeRequest $request, PlantType $plantType): PlantTypeResource
    {
        $plantType->fill($request->validated());
        $plantType->save();

        return PlantTypeResource::make($plantType);
    }

    public function destroy(Request $request, PlantType $plantType): Response
    {
        if ($request->user()?->role !== 'admin') {
            abort(Response::HTTP_FORBIDDEN, 'You are not allowed to delete plant types.');
        }

        $plantType->delete();

        return response()->noContent();
    }
}
