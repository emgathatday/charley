<?php

namespace App\Queries\Admin;

use App\Models\PlantType;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PartnerManagementQuery
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
            'account_type' => 'all',
            'status' => 'all',
            'subscription_tier_id' => (string) ($input['subscription_tier_id'] ?? ''),
        ];

        if (! in_array($filters['tab'], ['all', 'pending', 'frozen', 'suspended'], true)) {
            $filters['tab'] = 'all';
        }

        $plantTypeOptions = $this->plantTypeOptions();
        if ($filters['plant_type_id'] !== '' && ! in_array((int) $filters['plant_type_id'], array_map('intval', array_keys($plantTypeOptions)), true)) {
            $filters['plant_type_id'] = '';
        }

        $tierOptions = $this->subscriptionTierOptions();
        if ($filters['subscription_tier_id'] !== '' && ! in_array((int) $filters['subscription_tier_id'], array_map('intval', array_keys($tierOptions)), true)) {
            $filters['subscription_tier_id'] = '';
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
        $this->applyTier($query, $filters['subscription_tier_id']);
        $this->applyTab($query, $filters['tab']);

        return $query;
    }

    private function baseQuery(): Builder
    {
        $hasPartnerProfiles = Schema::hasTable('partner_profiles');
        $hasPlantTypes = Schema::hasTable('plant_types');

        $query = User::query()->select('users.*')->where('users.role', 'partner');

        if ($hasPartnerProfiles) {
            $query->leftJoin('partner_profiles', 'partner_profiles.user_id', '=', 'users.id')
                ->addSelect([
                    'partner_profiles.company_name as partner_company_name',
                    'partner_profiles.active_partner_subscription_id as partner_active_subscription_id',
                    'partner_profiles.subscription_expires_at as partner_subscription_expires_at',
                    'partner_profiles.contact_email as partner_contact_email',
                    'partner_profiles.country as partner_country',
                    'partner_profiles.website as partner_website',
                    'partner_profiles.subscription_status as partner_subscription_status',
                    'partner_profiles.keywords as partner_keywords',
                    'partner_profiles.founded_year as partner_founded_year',
                    'partner_profiles.approval_status as partner_approval_status',
                ]);

            if ($hasPlantTypes) {
                $query->leftJoin('plant_types as partner_plant_types', 'partner_plant_types.id', '=', 'partner_profiles.plant_type_id')
                    ->addSelect('partner_plant_types.name as partner_plant_type_name');
            } else {
                $query->addSelect(DB::raw('null as partner_plant_type_name'));
            }
        } else {
            $query->addSelect([
                DB::raw('null as partner_company_name'),
                DB::raw('null as partner_active_subscription_id'),
                DB::raw('null as partner_subscription_expires_at'),
                DB::raw('null as partner_contact_email'),
                DB::raw('null as partner_country'),
                DB::raw('null as partner_website'),
                DB::raw('null as partner_subscription_status'),
                DB::raw('null as partner_keywords'),
                DB::raw('null as partner_founded_year'),
                DB::raw('null as partner_approval_status'),
                DB::raw('null as partner_plant_type_name'),
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
                ->orWhereRaw('lower(partner_profiles.company_name) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(partner_profiles.website) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(partner_profiles.country) like ?', ["%{$keyword}%"]);
        });
    }

    private function applyPlantType(Builder $query, string $plantTypeId): void
    {
        if ($plantTypeId !== '') {
            $query->where('partner_profiles.plant_type_id', (int) $plantTypeId);
        }
    }

    private function applyTier(Builder $query, string $tierId): void
    {
        if ($tierId === '') {
            return;
        }

        $query->whereExists(function ($query) use ($tierId): void {
            $query->selectRaw('1')
                ->from('partner_subscriptions')
                ->whereColumn('partner_subscriptions.user_id', 'users.id')
                ->where('partner_subscriptions.status', 'active')
                ->where('partner_subscriptions.tier_id', (int) $tierId);
        });
    }

    private function applyTab(Builder $query, string $tab): void
    {
        match ($tab) {
            'pending' => $query->where('partner_profiles.approval_status', 'pending'),
            'frozen' => $query->where('users.status', 'frozen'),
            'suspended' => $query->where('users.status', 'suspended'),
            default => null,
        };
    }

    public function stats(): array
    {
        return [
            'total_users' => $this->baseQuery()->count('users.id'),
            'active_members' => $this->baseQuery()->where('users.status', 'active')->count('users.id'),
            'pending_approvals' => $this->baseQuery()->where('partner_profiles.approval_status', 'pending')->count('users.id'),
            'frozen_users' => $this->baseQuery()->where('users.status', 'frozen')->count('users.id'),
            'suspended_users' => $this->baseQuery()->where('users.status', 'suspended')->count('users.id'),
        ];
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

    public function subscriptionTierOptions(): array
    {
        if (! Schema::hasTable('subscription_tiers')) {
            return [];
        }

        return SubscriptionTier::query()->active()->orderBy('sort_order')->orderBy('display_name')->pluck('display_name', 'id')->all();
    }

    public function tierStats(): array
    {
        if (! Schema::hasTable('subscription_tiers') || ! Schema::hasTable('partner_subscriptions')) {
            return [];
        }

        return SubscriptionTier::query()
            ->active()
            ->withCount(['partnerSubscriptions as active_partners_count' => fn (Builder $query) => $query->active()])
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get()
            ->mapWithKeys(fn (SubscriptionTier $tier) => [(string) $tier->id => [
                'label' => $tier->display_name,
                'code' => $tier->code,
                'count' => $tier->active_partners_count,
            ]])
            ->all();
    }
}
