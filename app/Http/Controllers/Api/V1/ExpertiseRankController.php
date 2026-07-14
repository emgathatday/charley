<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpertiseRankRequest;
use App\Http\Resources\ExpertiseRankTierResource;
use App\Http\Resources\RankPromotionQuizLogResource;
use App\Http\Resources\UserExpertiseRankResource;
use App\Models\ExpertiseRankTier;
use App\Models\RankPromotionQuizLog;
use App\Models\User;
use App\Services\ExpertiseRankService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpertiseRankController extends Controller
{
    public function __construct(private readonly ExpertiseRankService $ranks)
    {
    }

    public function tiers(Request $request): AnonymousResourceCollection
    {
        return ExpertiseRankTierResource::collection(
            ExpertiseRankTier::query()
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
                ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
                ->orderBy('rank_order')
                ->paginate((int) $request->integer('per_page', 50))
        );
    }

    public function current(User $user): UserExpertiseRankResource
    {
        return UserExpertiseRankResource::make($this->ranks->currentRank($user));
    }

    public function setManual(ExpertiseRankRequest $request, User $user): UserExpertiseRankResource
    {
        return UserExpertiseRankResource::make($this->ranks->setManualRank(
            $user,
            (int) $request->validated('rank_tier_id'),
            $request->user(),
            $request->validated('note'),
        ));
    }

    public function evaluate(ExpertiseRankRequest $request, User $user): UserExpertiseRankResource
    {
        return UserExpertiseRankResource::make($this->ranks->evaluatePromotion($user, $request->integer('plant_type_id')));
    }

    public function logs(Request $request): AnonymousResourceCollection
    {
        return RankPromotionQuizLogResource::collection(
            RankPromotionQuizLog::query()->with('resultedPromotion.rankTier')->latest('created_at')->paginate((int) $request->integer('per_page', 25))
        );
    }
}