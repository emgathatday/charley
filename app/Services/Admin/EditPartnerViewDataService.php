<?php

namespace App\Services\Admin;

use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\PlantType;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class EditPartnerViewDataService
{
    public function data(User $user): array
    {
        $profile = $this->profile($user);
        $activeSubscription = $this->activeSubscription($user, $profile);
        $company = old('company_name', $profile->company_name ?? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Partner Company');
        $keywordChipItems = $this->keywordChipItems(old('keywords', $this->keywordsValue($profile->keywords ?? null)));
        $approvalStatus = old('approval_status', $profile->approval_status ?? ($user->is_verified ? 'approved' : 'pending'));

        return [
            'user' => $user,
            'profile' => $profile,
            'activeSubscription' => $activeSubscription,
            'company' => $company,
            'initials' => $this->initials($company),
            'partnerId' => '#PTN-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
            'partnerLogoUrl' => $this->partnerLogoUrl($profile),
            'plantTypeOptions' => $this->plantTypeOptions(),
            'subscriptionTiers' => $this->subscriptionTiers(),
            'keywordChipItems' => $keywordChipItems,
            'keywordsValue' => $keywordChipItems->implode(', '),
            'productItems' => $this->keywordChipItems(old('products', 'Catalyst supply, loading supervision, activation support, performance monitoring')),
            'selectedPlantType' => old('plant_type_id', $profile->plant_type_id ?? ''),
            'selectedTier' => old('subscription_tier_id', $activeSubscription->tier_id ?? ''),
            'selectedSubscriptionStatus' => old('subscription_status', $activeSubscription->status ?? $profile->subscription_status ?? 'inactive'),
            'approvalStatus' => $approvalStatus,
            'approvalStatusLabel' => str_replace('_', ' ', ucfirst((string) $approvalStatus)),
            'layoutTemplate' => old('layout_template', $profile->layout_template ?? 'layout_1'),
            'feedHighlightEnabled' => old('feed_highlight_enabled', $profile->feed_highlight_enabled ?? true),
            'joined' => $user->created_at?->format('d M Y') ?? 'Unknown',
            'tierName' => $activeSubscription?->tier?->display_name ?? 'No active tier',
            'renewalLabel' => $this->renewalLabel($profile, $activeSubscription),
            'approvalStatusOptions' => $this->approvalStatusOptions(),
            'layoutTemplateOptions' => $this->layoutTemplateOptions(),
            'subscriptionStatusOptions' => $this->subscriptionStatusOptions(),
            'accountStatusOptions' => $this->accountStatusOptions(),
        ];
    }

    private function profile(User $user): ?PartnerProfile
    {
        if (! Schema::hasTable('partner_profiles')) {
            return null;
        }

        return PartnerProfile::query()
            ->with($this->profileRelations())
            ->where('user_id', $user->id)
            ->first();
    }

    private function profileRelations(): array
    {
        $relations = [];
        if (Schema::hasTable('media_files')) {
            $relations[] = 'logoMedia';
        }
        if (Schema::hasTable('partner_subscriptions') && Schema::hasTable('subscription_tiers')) {
            $relations[] = 'activePartnerSubscription.tier';
        }

        return $relations;
    }

    private function activeSubscription(User $user, ?PartnerProfile $profile): ?PartnerSubscription
    {
        $activeSubscription = $profile?->activePartnerSubscription;
        if ($activeSubscription || ! Schema::hasTable('partner_subscriptions')) {
            return $activeSubscription;
        }

        return PartnerSubscription::query()
            ->with('tier')
            ->active()
            ->where('user_id', $user->id)
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    private function partnerLogoUrl(?PartnerProfile $profile): ?string
    {
        $mediaId = (int) ($profile->logo_media_id ?? 0);
        if ($mediaId <= 0 || ! Schema::hasTable('media_files')) {
            return null;
        }

        $media = MediaFile::query()->find($mediaId);
        if (! $media || ! $media->path) {
            return null;
        }

        try {
            return Storage::disk($media->disk ?: 'public')->url($media->path);
        } catch (\Throwable) {
            return null;
        }
    }

    private function plantTypeOptions(): array
    {
        if (! Schema::hasTable('plant_types')) {
            return [];
        }

        return PlantType::query()->active()->sorted()->pluck('name', 'id')->all();
    }

    private function subscriptionTiers(): Collection
    {
        if (! Schema::hasTable('subscription_tiers')) {
            return collect();
        }

        return SubscriptionTier::query()->active()->orderBy('sort_order')->orderBy('display_name')->get();
    }

    private function initials(string $company): string
    {
        return collect(explode(' ', trim($company)))
            ->filter()
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('') ?: 'P';
    }

    private function keywordsValue(mixed $keywords): string
    {
        if (is_string($keywords)) {
            $decodedKeywords = json_decode($keywords, true);
            $keywords = is_array($decodedKeywords) ? $decodedKeywords : explode(',', $keywords);
        }

        if (! is_array($keywords)) {
            return '';
        }

        return collect($keywords)->map(fn ($item) => trim((string) $item))->filter()->implode(', ');
    }

    private function keywordChipItems(mixed $value): Collection
    {
        return collect(explode(',', (string) $value))->map(fn (string $item) => trim($item))->filter()->values();
    }

    private function renewalLabel(?PartnerProfile $profile, ?PartnerSubscription $activeSubscription): string
    {
        $renewalDue = $profile?->subscription_expires_at ?? $activeSubscription?->ends_at ?? null;

        return $renewalDue ? Carbon::parse($renewalDue)->format('Y-m-d') : '';
    }

    private function approvalStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'suspended' => 'Suspended',
        ];
    }

    private function layoutTemplateOptions(): array
    {
        return [
            'layout_1' => 'Layout 1',
            'layout_2' => 'Layout 2',
            'layout_3' => 'Layout 3',
        ];
    }

    private function subscriptionStatusOptions(): array
    {
        return [
            'inactive' => 'Inactive',
            'pending_approval' => 'Pending Approval',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
        ];
    }

    private function accountStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'suspended' => 'Suspended',
            'frozen' => 'Frozen',
        ];
    }
}
