<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpertiseLevel;
use App\Models\PlantType;
use App\Models\User;
use App\Models\UserExpertiseRank;
use App\Services\Library\ExpertiseRankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpertiseRankController extends Controller
{
    public function __construct(
        private readonly ExpertiseRankingService $expertiseRankingService,
    ) {}

    public function index(): View
    {
        return view('admin.library.expertise-ranks.index', [
            'ranks' => UserExpertiseRank::query()
                ->with(['user', 'expertiseLevel', 'plantType', 'handbookCategory', 'assignedBy'])
                ->current()
                ->highestCurrent()
                ->paginate(20),
            'users' => User::query()->orderBy('email')->limit(100)->get(),
            'levels' => ExpertiseLevel::query()->active()->ordered()->get(),
            'plantTypes' => PlantType::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'expertise_level_id' => ['required', 'integer', 'exists:expertise_levels,id'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $rank = $this->expertiseRankingService->assignCvReviewRank(
            User::query()->findOrFail($data['user_id']),
            ExpertiseLevel::query()->findOrFail($data['expertise_level_id']),
            $request->user(),
            isset($data['plant_type_id']) ? PlantType::query()->findOrFail($data['plant_type_id']) : null,
            null,
            $data['notes'] ?? null,
        );

        return redirect()
            ->route('admin.dashboard.library.expertise-ranks.index')
            ->with('status', $rank ? 'Expertise rank assigned.' : 'Current rank is already equal or higher.');
    }
}
