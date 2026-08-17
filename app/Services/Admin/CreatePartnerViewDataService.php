<?php

namespace App\Services\Admin;

use App\Models\PlantType;
use App\Models\SubscriptionTier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CreatePartnerViewDataService
{
    /**
     * @return array<string, mixed>
     */
    public function data(mixed $oldKeywordValue = null): array
    {
        $subscriptionTiers = $this->subscriptionTiers();
        $tierCards = $this->tierCards($subscriptionTiers);

        return [
            'subscriptionTiers' => $subscriptionTiers,
            'selectedTierId' => (string) old('subscription_tier_id', optional($subscriptionTiers->first())->id),
            'defaultStartDate' => old('subscription_starts_at', now()->toDateString()),
            'tierCards' => $tierCards,
            'permissionHeaders' => $this->permissionHeaders($tierCards),
            'permissionRows' => $this->permissionRows($subscriptionTiers),
            'emptyPermissionCells' => $this->emptyPermissionCells($tierCards),
            'oldKeywords' => $this->oldKeywords($oldKeywordValue),
            'plantTypeItems' => $this->plantTypeItems(),
            'companyTypeOptions' => $this->companyTypeOptions(),
            'industrySegmentOptions' => $this->industrySegmentOptions(),
            'billingMethodOptions' => $this->billingMethodOptions(),
            'paymentStatusOptions' => $this->paymentStatusOptions(),
        ];
    }

    private function subscriptionTiers(): Collection
    {
        $canLoadTierPermissions = Schema::hasTable('subscription_tier_permissions') && Schema::hasTable('subscription_permissions');
        $query = SubscriptionTier::query()->active()->orderBy('sort_order')->orderBy('display_name');

        if ($canLoadTierPermissions) {
            $query->with('tierPermissions.permission');
        }

        $subscriptionTiers = $query->get();

        if (! $canLoadTierPermissions) {
            $subscriptionTiers->each(fn (SubscriptionTier $tier) => $tier->setRelation('tierPermissions', collect()));
        }

        return $subscriptionTiers;
    }

    private function tierCards(Collection $subscriptionTiers): Collection
    {
        $visualTierClasses = ['diamond', 'gold', 'platinum'];

        return $subscriptionTiers->values()->map(function (SubscriptionTier $tier, int $index) use ($visualTierClasses): array {
            $visualClass = $visualTierClasses[$index % count($visualTierClasses)];

            return [
                'tier' => $tier,
                'id' => (string) $tier->id,
                'visualClass' => $visualClass,
                'color' => $this->tierColor($visualClass),
                'iconLabel' => $this->tierIconLabel($visualClass),
                'price' => number_format((float) $tier->monthly_price, 2),
                'billingCycleLabel' => ucfirst(str_replace('_', ' ', (string) $tier->billing_cycle)),
                'features' => $this->tierFeatures($tier),
            ];
        });
    }

    private function permissionHeaders(Collection $tierCards): Collection
    {
        return $tierCards->map(fn (array $tierCard): array => [
            'id' => $tierCard['id'],
            'visualClass' => $tierCard['visualClass'],
            'color' => $tierCard['color'],
            'label' => $tierCard['iconLabel'].' '.$tierCard['tier']->display_name,
        ]);
    }

    private function permissionRows(Collection $subscriptionTiers): Collection
    {
        $permissions = $subscriptionTiers
            ->flatMap(fn (SubscriptionTier $tier) => $tier->tierPermissions)
            ->map(fn ($tierPermission) => $tierPermission->permission)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        return $permissions->map(function ($permission) use ($subscriptionTiers): array {
            return [
                'name' => $permission->name,
                'cells' => $subscriptionTiers->map(function (SubscriptionTier $tier) use ($permission): array {
                    $tierPermission = $tier->tierPermissions->firstWhere('permission_id', $permission->id);
                    $enabled = $this->permissionEnabled($tierPermission);

                    return [
                        'tier_id' => (string) $tier->id,
                        'class' => $enabled ? 'perm-yes' : 'perm-no',
                        'icon' => $enabled ? 'diamond-diamond-partner-licensor' : 'change-password-choose-strong',
                        'value_label' => $this->permissionValueLabel($tierPermission),
                    ];
                })->values(),
            ];
        });
    }

    private function emptyPermissionCells(Collection $tierCards): Collection
    {
        return $tierCards->map(fn (array $tierCard): array => [
            'tier_id' => $tierCard['id'],
            'class' => 'perm-no',
            'icon' => 'change-password-choose-strong',
        ]);
    }

    private function tierFeatures(SubscriptionTier $tier): Collection
    {
        return $tier->tierPermissions
            ->filter(fn ($tierPermission) => $tierPermission->permission)
            ->sortBy(fn ($tierPermission) => $tierPermission->permission->name)
            ->map(fn ($tierPermission): string => $tierPermission->permission->name.': '.$this->permissionValueLabel($tierPermission))
            ->values();
    }

    private function permissionValue(mixed $tierPermission): mixed
    {
        $value = $tierPermission?->value;

        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    private function permissionEnabled(mixed $tierPermission): bool
    {
        if (! $tierPermission) {
            return false;
        }

        $value = $this->permissionValue($tierPermission);
        $valueType = $tierPermission->permission?->value_type;

        if ($valueType === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($valueType === 'integer') {
            return (int) $value !== 0;
        }

        if (is_array($value)) {
            return ! empty(array_filter($value, fn ($item) => filled($item)));
        }

        return filled($value);
    }

    private function permissionValueLabel(mixed $tierPermission): string
    {
        if (! $tierPermission) {
            return 'Not included';
        }

        $permission = $tierPermission->permission;
        $value = $this->permissionValue($tierPermission);

        if ($permission?->value_type === 'boolean') {
            return $this->permissionEnabled($tierPermission) ? 'Included' : 'Not included';
        }

        if ($permission?->value_type === 'integer') {
            return (int) $value === -1 ? 'Unlimited' : number_format((int) $value);
        }

        if (is_array($value)) {
            return collect($value)->map(fn ($item, $key) => is_string($key) ? str($key)->headline().': '.$item : $item)->implode(', ');
        }

        return filled($value) ? (string) $value : 'Not included';
    }

    private function oldKeywords(mixed $oldKeywordValue): Collection
    {
        $decoded = is_array($oldKeywordValue) ? $oldKeywordValue : json_decode((string) $oldKeywordValue, true);

        return collect(is_array($decoded) ? $decoded : [])
            ->whenEmpty(fn () => collect(explode(',', (string) $oldKeywordValue)))
            ->map(fn ($keyword) => trim((string) $keyword))
            ->filter()
            ->unique()
            ->values();
    }

    private function plantTypeItems(): Collection
    {
        if (! Schema::hasTable('plant_types')) {
            return collect();
        }

        return PlantType::query()
            ->active()
            ->sorted()
            ->pluck('name', 'id')
            ->map(fn (string $label, int $value): array => ['value' => (string) $value, 'label' => $label])
            ->values();
    }

    private function companyTypeOptions(): array
    {
        return [
            'Licensor' => 'Licensor',
            'Catalyst Supplier' => 'Catalyst Supplier',
            'Vendor / Equipment Supplier' => 'Vendor / Equipment Supplier',
            'Consulting Company' => 'Consulting Company',
            'Engineering Company' => 'Engineering Company',
            'Manufacturing Facility' => 'Manufacturing Facility',
            'Technology Provider' => 'Technology Provider',
            'Service Provider' => 'Service Provider',
        ];
    }

    private function industrySegmentOptions(): array
    {
        return [
            'Ammonia' => 'Ammonia',
            'Methanol' => 'Methanol',
            'Hydrogen' => 'Hydrogen',
            'SNG' => 'SNG',
            'GTL' => 'GTL',
            'Multi-segment' => 'Multi-segment',
        ];
    }

    private function billingMethodOptions(): array
    {
        return [
            'bank_transfer' => 'Bank Transfer',
            'manual_invoice' => 'Manual Invoice',
            'other' => 'Other',
        ];
    }

    private function paymentStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'refunded' => 'Refunded',
        ];
    }

    private function tierColor(string $visualClass): string
    {
        return match ($visualClass) {
            'diamond' => '#1D4ED8',
            'gold' => '#92400E',
            default => '#6D28D9',
        };
    }

    private function tierIconLabel(string $visualClass): string
    {
        return match ($visualClass) {
            'diamond' => 'Diamond',
            'gold' => 'Gold',
            default => 'Platinum',
        };
    }
}
