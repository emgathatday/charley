<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quiz\CurrentExpertiseRankRequest;
use App\Http\Requests\Quiz\ExpertiseRankStoreRequest;
use App\Http\Resources\UserExpertiseRankResource;
use App\Models\ExpertiseLevel;
use App\Models\HandbookCategory;
use App\Models\PlantType;
use App\Models\User;
use App\Services\Library\ExpertiseRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExpertiseRankController extends Controller
{
    public function __construct(
        private readonly ExpertiseRankingService $expertiseRankingService,
    ) {}

    public function current(CurrentExpertiseRankRequest $request): JsonResponse|UserExpertiseRankResource
    {
        $data = $request->validated();
        $user = $this->rankSubject($request->user(), $data['user_id'] ?? null);

        $rank = $this->expertiseRankingService->bestCurrentRank(
            $user,
            $data['plant_type_id'] ?? null,
            $data['handbook_category_id'] ?? null,
        );

        if (! $rank) {
            return response()->json(['data' => null]);
        }

        return new UserExpertiseRankResource($rank->load(['expertiseLevel', 'plantType', 'handbookCategory']));
    }

    public function store(ExpertiseRankStoreRequest $request): JsonResponse|UserExpertiseRankResource
    {
        $data = $request->validated();
        $rank = $this->expertiseRankingService->assignCvReviewRank(
            User::query()->findOrFail($data['user_id']),
            ExpertiseLevel::query()->findOrFail($data['expertise_level_id']),
            $request->user(),
            isset($data['plant_type_id']) ? PlantType::query()->findOrFail($data['plant_type_id']) : null,
            isset($data['handbook_category_id']) ? HandbookCategory::query()->findOrFail($data['handbook_category_id']) : null,
            $data['notes'] ?? null,
        );

        if (! $rank) {
            return response()->json(['data' => null, 'message' => 'Existing current expertise rank is equal or higher.']);
        }

        return (new UserExpertiseRankResource($rank->load(['expertiseLevel', 'plantType', 'handbookCategory'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    private function rankSubject(User $viewer, ?int $userId): User
    {
        if ($userId !== null && $userId !== $viewer->id) {
            abort_unless($viewer->role === 'admin', Response::HTTP_FORBIDDEN);

            return User::query()->findOrFail($userId);
        }

        return $viewer;
    }
}
