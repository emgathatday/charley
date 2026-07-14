<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeDomainRequest;
use App\Http\Resources\KnowledgeDomainResource;
use App\Models\KnowledgeDomain;
use App\Services\KnowledgeDomainQuizService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class KnowledgeDomainController extends Controller
{
    public function __construct(private readonly KnowledgeDomainQuizService $domains)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return KnowledgeDomainResource::collection($this->domains->searchDomains($request->query(), (int) $request->integer('per_page', 15)));
    }

    public function store(KnowledgeDomainRequest $request): KnowledgeDomainResource
    {
        return KnowledgeDomainResource::make($this->domains->createDomain($request->validated(), $request->user()));
    }

    public function show(KnowledgeDomain $knowledgeDomain): KnowledgeDomainResource
    {
        return KnowledgeDomainResource::make($knowledgeDomain->load(['plantType', 'quizQuestions.choices']));
    }

    public function update(KnowledgeDomainRequest $request, KnowledgeDomain $knowledgeDomain): KnowledgeDomainResource
    {
        return KnowledgeDomainResource::make($this->domains->updateDomain($knowledgeDomain, $request->validated()));
    }

    public function destroy(KnowledgeDomain $knowledgeDomain): Response
    {
        $knowledgeDomain->delete();

        return response()->noContent();
    }
}
