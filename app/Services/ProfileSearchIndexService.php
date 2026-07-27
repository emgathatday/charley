<?php

namespace App\Services;

use App\Models\EngineerProfile;
use App\Models\SearchIndexEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProfileSearchIndexService
{
    public function refresh(Model $profile, string $context = 'expert_directory'): SearchIndexEntry
    {
        if (! $profile instanceof EngineerProfile) {
            throw new InvalidArgumentException('Only profile models can be indexed.');
        }

        return DB::transaction(function () use ($profile, $context): SearchIndexEntry {
            $profile->loadMissing(['user', 'plantTypes']);

            return SearchIndexEntry::query()->updateOrCreate(
                [
                    'indexable_type' => EngineerProfile::class,
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

    public function refreshAllExpertDirectory(): int
    {
        $count = 0;

        EngineerProfile::query()
            ->with(['user', 'plantTypes'])
            ->whereHas('user', fn (Builder $query): Builder => $query->whereIn('role', ['unverified_member', 'professional']))
            ->chunkById(100, function ($profiles) use (&$count): void {
                foreach ($profiles as $profile) {
                    $this->refresh($profile);
                    $count++;
                }
            });

        return $count;
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

    public function expertDirectoryQuery(?string $term = null, array $filters = []): Builder
    {
        return SearchIndexEntry::query()
            ->expertDirectory()
            ->discoverable()
            ->when($term, fn (Builder $query): Builder => $query->where('searchable_text', 'like', '%'.$term.'%'))
            ->when(isset($filters['plant_type_id']), function (Builder $query) use ($filters): Builder {
                return $query->whereJsonContains('structured_data->plant_type_ids', (int) $filters['plant_type_id']);
            })
            ->when(isset($filters['primary_plant_type_id']), function (Builder $query) use ($filters): Builder {
                return $query->where('structured_data->primary_plant_type_id', (int) $filters['primary_plant_type_id']);
            })
            ->when(isset($filters['job_availability']), function (Builder $query) use ($filters): Builder {
                return $query->where('structured_data->job_availability', $filters['job_availability']);
            });
    }

    private function searchableText(EngineerProfile $profile): string
    {
        $plantTypes = $profile->relationLoaded('plantTypes') ? $profile->plantTypes : collect();

        return trim(implode(' ', array_filter([
            $profile->current_company,
            $profile->current_institution,
            $profile->position,
            $profile->field_of_study,
            $profile->plant_name,
            $profile->bio,
            $profile->education,
            implode(' ', $profile->expertise_tags ?? []),
            implode(' ', $profile->industry_specialization ?? []),
            implode(' ', $profile->searchable_keywords ?? []),
            $plantTypes->pluck('name')->implode(' '),
            $plantTypes->pluck('slug')->implode(' '),
        ])));
    }

    private function structuredData(EngineerProfile $profile): array
    {
        $plantTypes = $profile->relationLoaded('plantTypes') ? $profile->plantTypes : collect();
        $primaryPlantType = $plantTypes->first(fn ($plantType): bool => (bool) $plantType->pivot?->is_primary);

        return [
            'profile_type' => EngineerProfile::class,
            'user_id' => $profile->user_id,
            'user_role' => $profile->relationLoaded('user') ? $profile->user?->role : null,
            'user_is_verified' => $profile->relationLoaded('user') ? (bool) $profile->user?->is_verified : null,
            'experience_years' => $profile->experience_years,
            'expertise_tags' => $profile->expertise_tags,
            'industry_specialization' => $profile->industry_specialization,
            'searchable_keywords' => $profile->searchable_keywords,
            'job_availability' => $profile->job_availability,
            'is_discoverable' => $profile->is_discoverable,
            'plant_type_ids' => $plantTypes->pluck('id')->map(fn (int|string $id): int => (int) $id)->values()->all(),
            'plant_types' => $plantTypes->map(fn ($plantType): array => [
                'id' => (int) $plantType->id,
                'name' => $plantType->name,
                'slug' => $plantType->slug,
                'is_primary' => (bool) $plantType->pivot?->is_primary,
                'sort_order' => (int) $plantType->pivot?->sort_order,
            ])->values()->all(),
            'primary_plant_type_id' => $primaryPlantType?->id,
        ];
    }
}
