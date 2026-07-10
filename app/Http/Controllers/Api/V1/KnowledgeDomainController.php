<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeDomain\KnowledgeDomainStoreRequest;
use App\Http\Requests\KnowledgeDomain\KnowledgeDomainUpdateRequest;
use App\Http\Resources\KnowledgeDomainResource;
use App\Models\KnowledgeDomain;
use App\Services\Library\KnowledgeDomainService;
use Illuminate\Http\Request;

class KnowledgeDomainController extends Controller
{
    public function __construct(private readonly KnowledgeDomainService $knowledgeDomainService)
    {
    }

    public function index(Request $request)
    {
        $query = KnowledgeDomain::query()->with('rankTiers')->withCount(['quizzes', 'hotspots']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($nested) use ($search): void {
                $nested->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        return KnowledgeDomainResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 15)));
    }

    public function store(KnowledgeDomainStoreRequest $request)
    {
        $data = $request->validated();
        $rankTiers = $data['rank_tiers'] ?? [];
        unset($data['rank_tiers']);
        $data['status'] ??= KnowledgeDomain::STATUS_ACTIVE;
        $data['created_by'] = $request->user()?->id;

        return new KnowledgeDomainResource($this->knowledgeDomainService->create($data, $rankTiers));
    }

    public function show(KnowledgeDomain $knowledgeDomain)
    {
        return new KnowledgeDomainResource($knowledgeDomain->load('rankTiers')->loadCount(['quizzes', 'hotspots']));
    }

    public function update(KnowledgeDomainUpdateRequest $request, KnowledgeDomain $knowledgeDomain)
    {
        return new KnowledgeDomainResource($this->knowledgeDomainService->update($knowledgeDomain, $request->validated())->load('rankTiers'));
    }

    public function archive(KnowledgeDomain $knowledgeDomain)
    {
        return new KnowledgeDomainResource($this->knowledgeDomainService->archive($knowledgeDomain));
    }
}