<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainRankTier;
use App\Models\KnowledgeDomain;
use App\Models\LibraryItemHotspot;
use App\Models\Quiz;
use App\Models\UserDomainPoint;
use App\Services\Library\KnowledgeDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KnowledgeDomainController extends Controller
{
    public function __construct(
        private readonly KnowledgeDomainService $knowledgeDomainService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'search']);

        return view('admin.library.knowledge-domains.index', [
            'filters' => $filters,
            'stats' => [
                'domains' => KnowledgeDomain::query()->count(),
                'active' => KnowledgeDomain::query()->active()->count(),
                'tiers' => DomainRankTier::query()->count(),
                'ranked_users' => UserDomainPoint::query()->ranked()->count(),
            ],
            'domains' => KnowledgeDomain::query()
                ->with(['rankTiers'])
                ->withCount(['quizzes', 'hotspots', 'userDomainPoints'])
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'statuses' => KnowledgeDomain::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->domainData($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['created_by'] = $request->user()?->id;

        $this->knowledgeDomainService->create($data);

        return redirect()->route('admin.dashboard.library.knowledge-domains.index')->with('status', 'Knowledge domain created.');
    }

    public function update(Request $request, KnowledgeDomain $knowledgeDomain): RedirectResponse
    {
        $data = $this->domainData($request, $knowledgeDomain);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $this->knowledgeDomainService->update($knowledgeDomain, $data);

        return redirect()->route('admin.dashboard.library.knowledge-domains.index')->with('status', 'Knowledge domain updated.');
    }

    public function archive(KnowledgeDomain $knowledgeDomain): RedirectResponse
    {
        $this->knowledgeDomainService->archive($knowledgeDomain);

        return redirect()->route('admin.dashboard.library.knowledge-domains.index')->with('status', 'Knowledge domain archived.');
    }

    public function storeRankTier(Request $request, KnowledgeDomain $knowledgeDomain): RedirectResponse
    {
        $this->knowledgeDomainService->createRankTier($knowledgeDomain, $this->tierData($request));

        return redirect()->route('admin.dashboard.library.knowledge-domains.index')->with('status', 'Rank tier added.');
    }

    public function updateRankTier(Request $request, DomainRankTier $domainRankTier): RedirectResponse
    {
        $this->knowledgeDomainService->updateRankTier($domainRankTier, $this->tierData($request));

        return redirect()->route('admin.dashboard.library.knowledge-domains.index')->with('status', 'Rank tier updated.');
    }

    public function destroyRankTier(DomainRankTier $domainRankTier): RedirectResponse
    {
        $domainRankTier->delete();

        return redirect()->route('admin.dashboard.library.knowledge-domains.index')->with('status', 'Rank tier removed.');
    }

    private function domainData(Request $request, ?KnowledgeDomain $knowledgeDomain = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('knowledge_domains', 'slug')->ignore($knowledgeDomain?->id)],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(KnowledgeDomain::STATUSES)],
        ]);
    }

    private function tierData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'min_points' => ['required', 'integer', 'min:0'],
            'badge_icon' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
    }
}