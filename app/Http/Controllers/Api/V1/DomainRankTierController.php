<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeDomain\DomainRankTierRequest;
use App\Http\Resources\DomainRankTierResource;
use App\Models\DomainRankTier;
use App\Models\KnowledgeDomain;
use App\Services\Library\KnowledgeDomainService;

class DomainRankTierController extends Controller
{
    public function __construct(private readonly KnowledgeDomainService $knowledgeDomainService)
    {
    }

    public function index(KnowledgeDomain $knowledgeDomain)
    {
        return DomainRankTierResource::collection($knowledgeDomain->rankTiers()->ordered()->get());
    }

    public function store(DomainRankTierRequest $request, KnowledgeDomain $knowledgeDomain)
    {
        return new DomainRankTierResource($this->knowledgeDomainService->createRankTier($knowledgeDomain, $request->validated()));
    }

    public function update(DomainRankTierRequest $request, DomainRankTier $domainRankTier)
    {
        return new DomainRankTierResource($this->knowledgeDomainService->updateRankTier($domainRankTier, $request->validated()));
    }

    public function destroy(DomainRankTier $domainRankTier)
    {
        $domainRankTier->delete();

        return response()->json(['deleted' => true]);
    }
}