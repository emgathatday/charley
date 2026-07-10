<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserDomainPointResource;
use App\Models\KnowledgeDomain;
use App\Models\UserDomainPoint;
use App\Services\Library\DomainRankingService;
use Illuminate\Http\Request;

class UserDomainPointController extends Controller
{
    public function __construct(private readonly DomainRankingService $domainRankingService)
    {
    }

    public function index(Request $request)
    {
        $query = UserDomainPoint::query()->with(['knowledgeDomain', 'currentRankTier']);

        if ($request->user()?->role === 'admin' && $request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        } else {
            $query->where('user_id', $request->user()->id);
        }

        return UserDomainPointResource::collection($query->orderByDesc('total_points')->get());
    }

    public function show(Request $request, KnowledgeDomain $knowledgeDomain)
    {
        $userId = $request->user()?->role === 'admin' && $request->filled('user_id')
            ? $request->integer('user_id')
            : $request->user()->id;

        $point = $this->domainRankingService->currentRank($userId, $knowledgeDomain)
            ?? $this->domainRankingService->recalculateUserDomainPoints($userId, $knowledgeDomain);

        return new UserDomainPointResource($point->load(['knowledgeDomain', 'currentRankTier']));
    }
}