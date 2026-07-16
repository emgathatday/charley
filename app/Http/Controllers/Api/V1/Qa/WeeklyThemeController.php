<?php

namespace App\Http\Controllers\Api\V1\Qa;

use App\Http\Controllers\Controller;
use App\Http\Resources\Qa\WeeklyThemeResource;
use App\Models\WeeklyTheme;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WeeklyThemeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $themes = WeeklyTheme::query()
            ->where('status', request('status', 'active'))
            ->orderByDesc('week_start_date')
            ->paginate((int) request('per_page', 20));

        return WeeklyThemeResource::collection($themes);
    }
}
