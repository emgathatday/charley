<?php

namespace App\Services;

use App\Models\PartnerMember;
use App\Models\PartnerPresentation;
use App\Models\PartnerProduct;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\PlantType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PartnerProfileService
{
    public function createProfile(array $data): PartnerProfile
    {
        return DB::transaction(function () use ($data): PartnerProfile {
            $profile = PartnerProfile::query()->create($this->profilePayload($data));

            $this->syncPlantTypes($profile, $data);
            $this->refreshSubscriptionCache($profile);

            return $profile->load($this->profileRelations());
        });
    }

    public function updateProfile(PartnerProfile $profile, array $data): PartnerProfile
    {
        return DB::transaction(function () use ($profile, $data): PartnerProfile {
            $profile->fill($this->profilePayload($data));
            $profile->save();

            $this->syncPlantTypes($profile, $data);
            $this->refreshSubscriptionCache($profile);

            return $profile->load($this->profileRelations());
        });
    }

    public function createProduct(PartnerProfile $profile, array $data): PartnerProduct
    {
        return DB::transaction(fn (): PartnerProduct => $profile->products()
            ->create($this->productPayload($data))
            ->load(['imageMedia', 'datasheetMedia']));
    }

    public function updateProduct(PartnerProduct $product, array $data): PartnerProduct
    {
        return DB::transaction(function () use ($product, $data): PartnerProduct {
            $product->fill($this->productPayload($data));
            $product->save();

            return $product->load(['imageMedia', 'datasheetMedia']);
        });
    }

    public function createPresentation(PartnerProfile $profile, array $data): PartnerPresentation
    {
        return DB::transaction(fn (): PartnerPresentation => $profile->presentations()
            ->create($this->presentationPayload($data))
            ->load(['plantType', 'fileMedia', 'approver']));
    }

    public function updatePresentation(PartnerPresentation $presentation, array $data): PartnerPresentation
    {
        return DB::transaction(function () use ($presentation, $data): PartnerPresentation {
            $presentation->fill($this->presentationPayload($data));
            $presentation->save();

            return $presentation->load(['plantType', 'fileMedia', 'approver']);
        });
    }

    public function createMember(PartnerProfile $profile, array $data): PartnerMember
    {
        return DB::transaction(fn (): PartnerMember => $profile->members()
            ->create($this->memberPayload($data))
            ->load(['user']));
    }

    public function updateMember(PartnerMember $member, array $data): PartnerMember
    {
        return DB::transaction(function () use ($member, $data): PartnerMember {
            $member->fill($this->memberPayload($data));
            $member->save();

            return $member->load(['user']);
        });
    }

    public function refreshSubscriptionCache(PartnerProfile $profile): void
    {
        $subscription = PartnerSubscription::query()
            ->active()
            ->where('user_id', $profile->user_id)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('starts_at')
            ->first();

        $profile->forceFill([
            'active_partner_subscription_id' => $subscription?->id,
            'subscription_status' => $subscription?->status ?? 'inactive',
            'subscription_expires_at' => $subscription?->ends_at,
        ])->save();
    }

    public function profileRelations(): array
    {
        return [
            'user',
            'logoMedia',
            'plantType',
            'activePartnerSubscription.tier',
            'plantTypes',
            'products.imageMedia',
            'products.datasheetMedia',
            'presentations.plantType',
            'presentations.fileMedia',
            'members.user',
        ];
    }

    private function syncPlantTypes(PartnerProfile $profile, array $data): void
    {
        if (! array_key_exists('plant_type_ids', $data) && ! array_key_exists('primary_plant_type_id', $data) && ! array_key_exists('plant_type_id', $data)) {
            return;
        }

        $plantTypeIds = array_values(array_unique(array_map(
            static fn ($plantTypeId): int => (int) $plantTypeId,
            $data['plant_type_ids'] ?? []
        )));

        if (isset($data['plant_type_id'])) {
            array_unshift($plantTypeIds, (int) $data['plant_type_id']);
            $plantTypeIds = array_values(array_unique($plantTypeIds));
        }

        $primaryPlantTypeId = isset($data['primary_plant_type_id'])
            ? (int) $data['primary_plant_type_id']
            : (isset($data['plant_type_id']) ? (int) $data['plant_type_id'] : null);

        if ($primaryPlantTypeId !== null && ! in_array($primaryPlantTypeId, $plantTypeIds, true)) {
            $plantTypeIds[] = $primaryPlantTypeId;
        }

        $activePlantTypeIds = PlantType::query()
            ->active()
            ->whereIn('id', $plantTypeIds)
            ->pluck('id')
            ->map(fn (int|string $plantTypeId): int => (int) $plantTypeId)
            ->all();

        $plantTypeIds = array_values(array_filter(
            $plantTypeIds,
            fn (int $plantTypeId): bool => in_array($plantTypeId, $activePlantTypeIds, true)
        ));

        if ($primaryPlantTypeId !== null && ! in_array($primaryPlantTypeId, $plantTypeIds, true)) {
            throw new RuntimeException('Primary plant type must be active and included in plant_type_ids.');
        }

        $syncPayload = [];

        foreach ($plantTypeIds as $sortOrder => $plantTypeId) {
            $syncPayload[$plantTypeId] = [
                'is_primary' => $primaryPlantTypeId !== null ? $plantTypeId === $primaryPlantTypeId : $sortOrder === 0,
                'sort_order' => $sortOrder,
            ];
        }

        $profile->plantTypes()->sync($syncPayload);

        $profile->forceFill(['plant_type_id' => $primaryPlantTypeId])->save();
    }

    private function profilePayload(array $data): array
    {
        return Arr::only($data, [
            'user_id',
            'company_name',
            'logo_media_id',
            'overview',
            'partner_tier',
            'plant_type_id',
            'company_type',
            'keywords',
            'references',
            'contact_email',
            'phone',
            'address',
            'country',
            'website',
            'founded_year',
            'social_links',
            'layout_template',
            'feed_highlight_enabled',
            'approval_status',
            'verified_at',
        ]);
    }

    private function productPayload(array $data): array
    {
        return Arr::only($data, [
            'name',
            'category',
            'item_type',
            'description',
            'image_media_id',
            'datasheet_media_id',
            'keywords',
            'is_active',
        ]);
    }

    private function presentationPayload(array $data): array
    {
        return Arr::only($data, [
            'title',
            'slug',
            'description',
            'plant_type_id',
            'equipment_category',
            'page_count',
            'download_allowed',
            'view_count',
            'status',
            'approved_by',
            'approved_at',
            'rejection_reason',
            'is_ai_trainable',
            'file_media_id',
        ]);
    }

    private function memberPayload(array $data): array
    {
        return Arr::only($data, [
            'user_id',
            'member_role',
            'joined_at',
            'status',
        ]);
    }
}
