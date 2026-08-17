<?php

namespace App\Http\Controllers\Api\V1\Admin\Qa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Qa\WeeklyThemeRequest;
use App\Http\Resources\Admin\Qa\WeeklyThemeAdminResource;
use App\Models\WeeklyTheme;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WeeklyThemeManagementController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $themes = WeeklyTheme::query()
            ->with('createdByAdmin')
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('week_start_date')
            ->paginate((int) request('per_page', 20));

        return WeeklyThemeAdminResource::collection($themes);
    }

    public function store(WeeklyThemeRequest $request): WeeklyThemeAdminResource
    {
        $theme = WeeklyTheme::query()->create($request->validated() + [
            'created_by_admin_id' => $request->user()->id,
            'status' => $request->validated('status', 'active'),
        ]);

        return new WeeklyThemeAdminResource($theme->load('createdByAdmin'));
    }

    public function show(WeeklyTheme $weeklyTheme): WeeklyThemeAdminResource
    {
        return new WeeklyThemeAdminResource($weeklyTheme->load('createdByAdmin'));
    }

    public function update(WeeklyThemeRequest $request, WeeklyTheme $weeklyTheme): WeeklyThemeAdminResource
    {
        $weeklyTheme->update($request->validated());

        return new WeeklyThemeAdminResource($weeklyTheme->refresh()->load('createdByAdmin'));
    }

    public function activate(WeeklyTheme $weeklyTheme): WeeklyThemeAdminResource
    {
        $weeklyTheme->update(['status' => 'active']);

        return new WeeklyThemeAdminResource($weeklyTheme->refresh()->load('createdByAdmin'));
    }

    public function archive(WeeklyTheme $weeklyTheme): WeeklyThemeAdminResource
    {
        $weeklyTheme->update(['status' => 'archived']);

        return new WeeklyThemeAdminResource($weeklyTheme->refresh()->load('createdByAdmin'));
    }
}
