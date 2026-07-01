<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlantType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlantTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.plant-types.index', [
            'plantTypes' => PlantType::query()->sorted()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.plant-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        PlantType::query()->create($this->validatedPlantType($request));

        return redirect()
            ->route('admin.dashboard.plant-types.index')
            ->with('status', 'Plant type created.');
    }

    public function edit(PlantType $plantType): View
    {
        return view('admin.plant-types.edit', ['plantType' => $plantType]);
    }

    public function update(Request $request, PlantType $plantType): RedirectResponse
    {
        $plantType->fill($this->validatedPlantType($request, $plantType));
        $plantType->save();

        return redirect()
            ->route('admin.dashboard.plant-types.edit', $plantType)
            ->with('status', 'Plant type updated.');
    }

    public function destroy(PlantType $plantType): RedirectResponse
    {
        $plantType->delete();

        return redirect()
            ->route('admin.dashboard.plant-types.index')
            ->with('status', 'Plant type deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPlantType(Request $request, ?PlantType $plantType = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('plant_types', 'name')->ignore($plantType?->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('plant_types', 'slug')->ignore($plantType?->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
    }
}