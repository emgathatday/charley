<?php

namespace App\Queries\Admin;

use App\Models\PlantType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EngineerManagementQuery
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function filters(array $input): array
    {
        $filters = [
            'keyword' => trim((string) ($input['keyword'] ?? $input['search'] ?? '')),
            'plant_type_id' => (string) ($input['plant_type_id'] ?? ''),
            'tab' => (string) ($input['tab'] ?? 'all'),
            'account_type' => (string) ($input['account_type'] ?? 'all'),
            'status' => (string) ($input['status'] ?? 'all'),
            'subscription_tier_id' => '',
        ];

        if (! in_array($filters['tab'], ['all', 'professional', 'registered', 'restricted', 'pending', 'frozen', 'suspended'], true)) {
            $filters['tab'] = 'all';
        }

        if (! in_array($filters['account_type'], ['all', 'registered', 'professional'], true)) {
            $filters['account_type'] = 'all';
        }

        if (! in_array($filters['status'], ['all', 'active', 'pending', 'suspended', 'frozen'], true)) {
            $filters['status'] = 'all';
        }

        $plantTypeOptions = $this->plantTypeOptions();
        if ($filters['plant_type_id'] !== '' && ! in_array((int) $filters['plant_type_id'], array_map('intval', array_keys($plantTypeOptions)), true)) {
            $filters['plant_type_id'] = '';
        }

        return $filters;
    }

    public function query(array $filters): Builder
    {
        $query = $this->baseQuery()
            ->withCount([
                'verificationRequests',
                'verificationRequests as pending_verification_requests_count' => fn (Builder $query) => $query->where('status', 'pending'),
            ]);

        $this->applyKeyword($query, $filters['keyword']);
        $this->applyPlantType($query, $filters['plant_type_id']);
        $this->applyAccountType($query, $filters['account_type']);
        $this->applyStatus($query, $filters['status']);
        $this->applyTab($query, $filters['tab']);

        return $query;
    }

    private function baseQuery(): Builder
    {
        $hasEngineerProfiles = Schema::hasTable('engineer_profiles');
        $hasUnverifiedProfiles = Schema::hasTable('unverified_member_profiles');
        $hasEngineerProfilePhoto = $hasEngineerProfiles && Schema::hasColumn('engineer_profiles', 'photo_media_id');
        $hasUnverifiedProfilePhoto = $hasUnverifiedProfiles && Schema::hasColumn('unverified_member_profiles', 'photo_media_id');

        $query = User::query()->select('users.*')->whereIn('users.role', ['professional', 'unverified_member']);

        if ($hasEngineerProfiles) {
            $query->leftJoin('engineer_profiles', 'engineer_profiles.user_id', '=', 'users.id')
                ->addSelect([
                    'engineer_profiles.industry_specialization as engineer_industry_specialization',
                    'engineer_profiles.expertise_tags as engineer_expertise_tags',
                    'engineer_profiles.field_of_study as unverified_field_of_study',
                    'engineer_profiles.expertise_tags as unverified_expertise_tags',
                    'engineer_profiles.experience_years as engineer_experience_years',
                    'engineer_profiles.experience_years as unverified_experience_years',
                    $hasEngineerProfilePhoto ? 'engineer_profiles.photo_media_id as engineer_photo_media_id' : DB::raw('null as engineer_photo_media_id'),
                ]);
        } else {
            $query->addSelect([
                DB::raw('null as engineer_industry_specialization'),
                DB::raw('null as engineer_expertise_tags'),
                DB::raw('null as unverified_field_of_study'),
                DB::raw('null as unverified_expertise_tags'),
                DB::raw('null as engineer_experience_years'),
                DB::raw('null as unverified_experience_years'),
                DB::raw('null as engineer_photo_media_id'),
            ]);
        }

        if ($hasUnverifiedProfiles) {
            $query->leftJoin('unverified_member_profiles', 'unverified_member_profiles.user_id', '=', 'users.id')
                ->addSelect([
                    $hasUnverifiedProfilePhoto ? 'unverified_member_profiles.photo_media_id as unverified_photo_media_id' : DB::raw('null as unverified_photo_media_id'),
                ]);
        } else {
            $query->addSelect(DB::raw('null as unverified_photo_media_id'));
        }

        if ($hasEngineerProfiles && $this->hasPlantTypePivot()) {
            $query
                ->selectSub($this->plantTypeNamesSubquery(), 'engineer_plant_type_names')
                ->selectSub($this->plantTypeNamesSubquery(), 'unverified_plant_type_names');
        } else {
            $query->addSelect([
                DB::raw('null as engineer_plant_type_names'),
                DB::raw('null as unverified_plant_type_names'),
            ]);
        }

        return $query;
    }

    private function applyKeyword(Builder $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $keyword = strtolower($keyword);
        $query->where(function (Builder $query) use ($keyword): void {
            $query->whereRaw('lower(users.username) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(users.first_name) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(users.last_name) like ?', ["%{$keyword}%"])
                ->orWhereRaw("lower(coalesce(users.first_name, '') || ' ' || coalesce(users.last_name, '')) like ?", ["%{$keyword}%"])
                ->orWhereRaw('lower(users.email) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(engineer_profiles.current_company) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(engineer_profiles.current_institution) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(engineer_profiles.position) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(engineer_profiles.field_of_study) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(engineer_profiles.plant_name) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower('.$this->jsonTextColumn('engineer_profiles.expertise_tags').') like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower('.$this->jsonTextColumn('engineer_profiles.industry_specialization').') like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower('.$this->jsonTextColumn('engineer_profiles.searchable_keywords').') like ?', ["%{$keyword}%"]);
        });
    }

    private function jsonTextColumn(string $column): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? "{$column}::text" : $column;
    }

    private function applyPlantType(Builder $query, string $plantTypeId): void
    {
        if ($plantTypeId === '') {
            return;
        }

        if (! $this->hasPlantTypePivot()) {
            $query->whereRaw('0 = 1');
            return;
        }

        $query->whereExists(function ($query) use ($plantTypeId): void {
            $query->selectRaw('1')
                ->from('engineer_profile_plant_type')
                ->whereColumn('engineer_profile_plant_type.engineer_profile_id', 'engineer_profiles.id')
                ->where('engineer_profile_plant_type.plant_type_id', (int) $plantTypeId);
        });
    }

    private function applyAccountType(Builder $query, string $accountType): void
    {
        if ($accountType === 'registered') {
            $query->where('users.role', 'unverified_member');
        } elseif ($accountType === 'professional') {
            $query->where('users.role', 'professional');
        }
    }

    private function applyStatus(Builder $query, string $status): void
    {
        if ($status === 'all') {
            return;
        }

        if ($status === 'pending') {
            $this->scopePending($query);
            return;
        }

        $query->where('users.status', $status);
    }

    private function applyTab(Builder $query, string $tab): void
    {
        match ($tab) {
            'professional' => $query->where('users.role', 'professional'),
            'registered' => $query->where('users.role', 'unverified_member'),
            'restricted' => $query->whereIn('users.status', ['suspended', 'frozen']),
            'pending' => $this->scopePending($query),
            'frozen' => $query->where('users.status', 'frozen'),
            'suspended' => $query->where('users.status', 'suspended'),
            default => null,
        };
    }

    private function scopePending(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->where('users.is_verified', false)
                ->orWhereHas('verificationRequests', fn (Builder $query) => $query->where('status', 'pending'));
        });
    }

    public function stats(): array
    {
        $pending = $this->baseQuery();
        $this->scopePending($pending);

        return [
            'total_users' => $this->baseQuery()->count('users.id'),
            'active_members' => $this->baseQuery()->where('users.status', 'active')->count('users.id'),
            'pending_approvals' => $pending->count('users.id'),
            'frozen_users' => $this->baseQuery()->where('users.status', 'frozen')->count('users.id'),
            'suspended_users' => $this->baseQuery()->where('users.status', 'suspended')->count('users.id'),
            'professional_users' => $this->baseQuery()->where('users.role', 'professional')->count('users.id'),
            'registered_members' => $this->baseQuery()->where('users.role', 'unverified_member')->count('users.id'),
        ];
    }

    private function hasPlantTypePivot(): bool
    {
        return Schema::hasTable('engineer_profile_plant_type')
            && Schema::hasTable('plant_types');
    }

    private function plantTypeNamesSubquery(): string
    {
        $aggregate = DB::connection()->getDriverName() === 'sqlite'
            ? "group_concat(plant_types.name, ', ')"
            : "string_agg(plant_types.name, ', ' order by engineer_profile_plant_type.sort_order, plant_types.name)";

        return "select {$aggregate} from engineer_profile_plant_type inner join plant_types on plant_types.id = engineer_profile_plant_type.plant_type_id where engineer_profile_plant_type.engineer_profile_id = engineer_profiles.id";
    }

    public function plantTypeOptions(): array
    {
        if (! Schema::hasTable('plant_types')) {
            return [];
        }

        return PlantType::query()
            ->active()
            ->sorted()
            ->pluck('name', 'id')
            ->all();
    }

}
