<?php

namespace App\Services;

use App\Models\EngineerProfile;
use App\Models\SearchIndexEntry;
use App\Models\UnverifiedMemberProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProfileSearchIndexService
{
    public function refresh(Model $profile, string $context = 'expert_directory'): SearchIndexEntry
    {
        if (! $profile instanceof EngineerProfile && ! $profile instanceof UnverifiedMemberProfile) {
            throw new InvalidArgumentException('Only profile models can be indexed.');
        }

        return DB::transaction(function () use ($profile, $context): SearchIndexEntry {
            return SearchIndexEntry::query()->updateOrCreate(
                [
                    'indexable_type' => $profile::class,
                    'indexable_id' => $profile->id,
                    'search_context' => $context,
                ],
                [
                    'searchable_text' => $this->searchableText($profile),
                    'structured_data' => $this->structuredData($profile),
                    'is_discoverable' => (bool) $profile->is_discoverable,
                    'last_indexed_at' => now(),
                ]
            );
        });
    }

    public function remove(Model $profile, ?string $context = null): int
    {
        $query = SearchIndexEntry::query()
            ->where('indexable_type', $profile::class)
            ->where('indexable_id', $profile->id);

        if ($context !== null) {
            $query->where('search_context', $context);
        }

        return $query->delete();
    }

    public function expertDirectoryQuery(?string $term = null): Builder
    {
        return SearchIndexEntry::query()
            ->expertDirectory()
            ->discoverable()
            ->when($term, fn (Builder $query): Builder => $query->where('searchable_text', 'like', '%'.$term.'%'));
    }

    private function searchableText(EngineerProfile|UnverifiedMemberProfile $profile): string
    {
        return trim(implode(' ', array_filter([
            $profile instanceof EngineerProfile ? $profile->current_company : $profile->current_institution,
            $profile instanceof EngineerProfile ? $profile->position : $profile->field_of_study,
            $profile->bio,
            implode(' ', $profile->expertise_tags ?? []),
            implode(' ', $profile->searchable_keywords ?? []),
        ])));
    }

    private function structuredData(EngineerProfile|UnverifiedMemberProfile $profile): array
    {
        return [
            'profile_type' => $profile::class,
            'user_id' => $profile->user_id,
            'experience_years' => $profile->experience_years,
            'expertise_tags' => $profile->expertise_tags,
            'searchable_keywords' => $profile->searchable_keywords,
            'job_availability' => $profile->job_availability,
            'is_discoverable' => $profile->is_discoverable,
        ];
    }
}
