<?php

namespace App\Services\Library;

use App\Models\DomainRankTier;
use App\Models\KnowledgeDomain;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class KnowledgeDomainService
{
    public function create(array $attributes, array $rankTiers = []): KnowledgeDomain
    {
        return DB::transaction(function () use ($attributes, $rankTiers): KnowledgeDomain {
            $domain = KnowledgeDomain::query()->create($attributes);

            if ($rankTiers !== []) {
                $this->replaceRankTiers($domain, $rankTiers);
            }

            return $domain->load('rankTiers');
        });
    }

    public function update(KnowledgeDomain $knowledgeDomain, array $attributes): KnowledgeDomain
    {
        return DB::transaction(function () use ($knowledgeDomain, $attributes): KnowledgeDomain {
            $knowledgeDomain->update($attributes);

            return $knowledgeDomain->refresh();
        });
    }

    public function archive(KnowledgeDomain $knowledgeDomain): KnowledgeDomain
    {
        return $this->update($knowledgeDomain, ['status' => KnowledgeDomain::STATUS_ARCHIVED]);
    }

    public function activate(KnowledgeDomain $knowledgeDomain): KnowledgeDomain
    {
        return $this->update($knowledgeDomain, ['status' => KnowledgeDomain::STATUS_ACTIVE]);
    }

    public function createRankTier(KnowledgeDomain $knowledgeDomain, array $attributes): DomainRankTier
    {
        return DB::transaction(function () use ($knowledgeDomain, $attributes): DomainRankTier {
            $tier = $knowledgeDomain->rankTiers()->create($attributes);
            $this->assertValidTierSequence($knowledgeDomain->refresh());

            return $tier->refresh();
        });
    }

    public function updateRankTier(DomainRankTier $rankTier, array $attributes): DomainRankTier
    {
        return DB::transaction(function () use ($rankTier, $attributes): DomainRankTier {
            $rankTier->update($attributes);
            $this->assertValidTierSequence($rankTier->knowledgeDomain()->firstOrFail());

            return $rankTier->refresh();
        });
    }

    public function replaceRankTiers(KnowledgeDomain $knowledgeDomain, array $rankTiers): KnowledgeDomain
    {
        return DB::transaction(function () use ($knowledgeDomain, $rankTiers): KnowledgeDomain {
            $this->assertValidTierPayload($rankTiers);

            $knowledgeDomain->rankTiers()->delete();
            foreach ($rankTiers as $rankTier) {
                $knowledgeDomain->rankTiers()->create($rankTier);
            }

            return $knowledgeDomain->refresh()->load('rankTiers');
        });
    }

    private function assertValidTierPayload(array $rankTiers): void
    {
        $previousSortOrder = null;
        $previousMinPoints = null;

        foreach ($rankTiers as $rankTier) {
            $sortOrder = (int) ($rankTier['sort_order'] ?? 0);
            $minPoints = (int) ($rankTier['min_points'] ?? 0);

            if ($previousSortOrder !== null && $sortOrder <= $previousSortOrder) {
                throw new InvalidArgumentException('Domain rank tiers must use increasing sort_order values.');
            }

            if ($previousMinPoints !== null && $minPoints < $previousMinPoints) {
                throw new InvalidArgumentException('Domain rank tiers must use non-decreasing min_points values.');
            }

            $previousSortOrder = $sortOrder;
            $previousMinPoints = $minPoints;
        }
    }

    private function assertValidTierSequence(KnowledgeDomain $knowledgeDomain): void
    {
        $this->assertValidTierPayload(
            $knowledgeDomain->rankTiers()->orderBy('sort_order')->get(['min_points', 'sort_order'])->all(),
        );
    }
}