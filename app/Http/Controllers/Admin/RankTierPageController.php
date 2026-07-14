<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpertiseRankTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RankTierPageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:active,draft,deleted'],
        ]);
        $status = $filters['status'] ?? 'active';

        $rankTiers = ExpertiseRankTier::query()
            ->where('status', $status)
            ->orderBy('rank_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.library.rank-tiers', [
            'rankTiers' => $rankTiers,
            'filters' => ['status' => $status],
            'statusCounts' => ExpertiseRankTier::query()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->all(),
            'stats' => [
                'tiers' => ExpertiseRankTier::query()->count(),
                'active_tiers' => ExpertiseRankTier::query()->where('status', 'active')->count(),
                'draft_tiers' => ExpertiseRankTier::query()->where('status', 'draft')->count(),
                'deleted_tiers' => ExpertiseRankTier::query()->where('status', 'deleted')->count(),
            ],
        ]);
    }

    public function clone(Request $request, ExpertiseRankTier $rankTier): RedirectResponse
    {
        $clone = $rankTier->replicate();
        $clone->fill([
            'name' => $this->uniqueCloneName($rankTier->name),
            'slug' => $this->uniqueCloneSlug($rankTier->slug),
            'rank_order' => $this->nextRankOrder(),
            'status' => 'draft',
            'is_active' => false,
        ])->save();

        return redirect()
            ->route('admin.dashboard.library.rank-tiers.index', ['status' => 'draft'])
            ->with('status', 'Rank tier cloned as draft.');
    }

    public function updateStatus(Request $request, ExpertiseRankTier $rankTier): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,draft,deleted'],
        ]);

        $rankTier->forceFill([
            'status' => $data['status'],
            'is_active' => $data['status'] === 'active',
        ])->save();

        return redirect()
            ->route('admin.dashboard.library.rank-tiers.index', ['status' => $data['status']])
            ->with('status', 'Rank tier status updated.');
    }

    public function destroy(ExpertiseRankTier $rankTier): RedirectResponse
    {
        $rankTier->forceFill([
            'status' => 'deleted',
            'is_active' => false,
        ])->save();

        return redirect()
            ->route('admin.dashboard.library.rank-tiers.index', ['status' => 'deleted'])
            ->with('status', 'Rank tier moved to deleted.');
    }
    public function create(): View
    {
        return view('admin.library.rank-tiers.create', [
            'rankTier' => $this->blankRankTier(),
            'mandatoryDomains' => $this->mandatoryDomains(),
            'promotionRules' => $this->promotionRules(),
            'plantTypes' => $this->plantTypes(),
            'knowledgeDomains' => $this->knowledgeDomains(),
        ]);
    }

    public function edit(ExpertiseRankTier $rankTier): View
    {
        return view('admin.library.rank-tiers.edit', [
            'rankTier' => $rankTier,
        ]);
    }

    public function update(Request $request, ExpertiseRankTier $rankTier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expertise_rank_tiers,name,'.$rankTier->id],
            'slug' => ['required', 'string', 'max:255', 'unique:expertise_rank_tiers,slug,'.$rankTier->id],
            'min_years_experience' => ['nullable', 'integer', 'min:0'],
            'default_cap_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'rank_order' => ['required', 'integer', 'min:0', 'unique:expertise_rank_tiers,rank_order,'.$rankTier->id],
            'required_quiz_count' => ['required', 'integer', 'min:0'],
            'required_mandatory_quiz_count' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,draft,deleted'],
        ]);

        $data['is_active'] = $data['status'] === 'active';

        $rankTier->update($data);

        return redirect()
            ->route('admin.dashboard.library.rank-tiers.edit', $rankTier)
            ->with('status', 'Rank tier updated.');
    }

    private function uniqueCloneName(string $name): string
    {
        $base = $name.' Copy';
        $candidate = $base;
        $suffix = 2;

        while (ExpertiseRankTier::query()->where('name', $candidate)->exists()) {
            $candidate = $base.' '.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function uniqueCloneSlug(string $slug): string
    {
        $base = Str::slug($slug.' copy');
        $candidate = $base;
        $suffix = 2;

        while (ExpertiseRankTier::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function nextRankOrder(): int
    {
        return ((int) ExpertiseRankTier::query()->max('rank_order')) + 1;
    }
    private function findRankTier(int $id): array
    {
        foreach ($this->rankTiers() as $rankTier) {
            if ($rankTier['id'] === $id) {
                return $rankTier;
            }
        }

        return $this->rankTiers()[0];
    }

    private function blankRankTier(): array
    {
        return [
            'id' => null,
            'name' => '',
            'slug' => '',
            'min_years_experience' => 0,
            'default_cap_percentage' => 30,
            'rank_order' => 50,
            'required_quiz_count' => 10,
            'required_mandatory_quiz_count' => 3,
            'status' => 'draft',
            'is_active' => true,
            'current_users' => 0,
            'promotion_readiness' => 0,
        ];
    }

    private function rankTiers(): array
    {
        return [
            [
                'id' => 301,
                'name' => 'Associate',
                'slug' => 'associate',
                'min_years_experience' => 0,
                'default_cap_percentage' => 35,
                'rank_order' => 10,
                'required_quiz_count' => 4,
                'required_mandatory_quiz_count' => 1,
                'is_active' => true,
                'current_users' => 128,
                'promotion_readiness' => 64,
            ],
            [
                'id' => 302,
                'name' => 'Professional',
                'slug' => 'professional',
                'min_years_experience' => 3,
                'default_cap_percentage' => 55,
                'rank_order' => 20,
                'required_quiz_count' => 8,
                'required_mandatory_quiz_count' => 2,
                'is_active' => true,
                'current_users' => 74,
                'promotion_readiness' => 48,
            ],
            [
                'id' => 303,
                'name' => 'Senior Specialist',
                'slug' => 'senior-specialist',
                'min_years_experience' => 6,
                'default_cap_percentage' => 70,
                'rank_order' => 30,
                'required_quiz_count' => 10,
                'required_mandatory_quiz_count' => 3,
                'is_active' => true,
                'current_users' => 38,
                'promotion_readiness' => 31,
            ],
            [
                'id' => 304,
                'name' => 'Principal Expert',
                'slug' => 'principal-expert',
                'min_years_experience' => 10,
                'default_cap_percentage' => 85,
                'rank_order' => 40,
                'required_quiz_count' => 14,
                'required_mandatory_quiz_count' => 5,
                'is_active' => true,
                'current_users' => 12,
                'promotion_readiness' => 18,
            ],
            [
                'id' => 305,
                'name' => 'Legacy Reviewer',
                'slug' => 'legacy-reviewer',
                'min_years_experience' => 12,
                'default_cap_percentage' => 60,
                'rank_order' => 90,
                'required_quiz_count' => 6,
                'required_mandatory_quiz_count' => 2,
                'is_active' => false,
                'current_users' => 5,
                'promotion_readiness' => 0,
            ],
        ];
    }

    private function mandatoryDomains(): array
    {
        return [
            ['id' => 401, 'plant_type' => 'Combined Cycle', 'knowledge_domain' => 'Gas Turbine Operations', 'is_active' => true, 'assigned_tiers' => ['Professional', 'Senior Specialist', 'Principal Expert']],
            ['id' => 402, 'plant_type' => 'Combined Cycle', 'knowledge_domain' => 'Heat Recovery Steam Generator', 'is_active' => true, 'assigned_tiers' => ['Senior Specialist', 'Principal Expert']],
            ['id' => 403, 'plant_type' => 'Transmission', 'knowledge_domain' => 'Transformer Diagnostics', 'is_active' => true, 'assigned_tiers' => ['Professional', 'Senior Specialist']],
            ['id' => 404, 'plant_type' => 'Solar Utility', 'knowledge_domain' => 'Arc Flash Safety', 'is_active' => false, 'assigned_tiers' => ['Associate']],
        ];
    }

    private function promotionRules(): array
    {
        return [
            ['label' => 'Quiz pathway', 'value' => 'Pass required unique domains and mandatory plant domains before automatic promotion.', 'badge' => 'Active'],
            ['label' => 'Manual review', 'value' => 'Admin can assign a current rank when profile evidence justifies override.', 'badge' => 'Admin'],
            ['label' => 'Default cap', 'value' => 'Unpassed domains use the current rank default cap percentage until quiz pass unlocks 100%.', 'badge' => 'Visible'],
        ];
    }

    private function plantTypes(): array
    {
        return ['Combined Cycle', 'Transmission', 'Solar Utility', 'Hydro', 'General'];
    }

    private function knowledgeDomains(): array
    {
        return ['Gas Turbine Operations', 'Heat Recovery Steam Generator', 'Transformer Diagnostics', 'Arc Flash Safety', 'Hydro Governor Systems'];
    }
}