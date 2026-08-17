<?php

namespace App\Services;

use App\Models\EngineerProfile;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProfileService
{
    public function __construct(
        private readonly ProfileSearchIndexService $searchIndexService
    ) {}

    public function upsertEngineerProfile(User $user, array $data, bool $enforceActiveUser = true): EngineerProfile
    {
        if ($enforceActiveUser) {
            $this->ensureActiveUser($user);
        }

        if (! in_array($user->role, ['unverified_member', 'professional'], true)) {
            throw new RuntimeException('Only personal users can have engineer profiles.');
        }

        return DB::transaction(function () use ($user, $data): EngineerProfile {
            $profile = EngineerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $this->filterEngineerData($data)
            );

            $this->syncProfilePlantTypes($profile, $data);
            $profile->load(['user', 'plantTypes']);
            $this->searchIndexService->refresh($profile);

            return $profile;
        });
    }

    public function upsertUnverifiedMemberProfile(User $user, array $data, bool $enforceActiveUser = true): EngineerProfile
    {
        if ($user->role !== 'unverified_member') {
            throw new RuntimeException('Only unverified members can have unverified member profiles.');
        }

        return $this->upsertEngineerProfile($user, $data, $enforceActiveUser);
    }

    public function requestVerification(User $user, array $data = []): EngineerProfile
    {
        return DB::transaction(function () use ($user, $data): EngineerProfile {
            $profile = $this->upsertEngineerProfile($user, array_merge($data, [
                'verification_intent' => true,
            ]));

            $user->forceFill([
                'is_verified' => false,
                'verified_at' => null,
                'verification_expires_at' => null,
            ])->save();

            return $profile->load(['user', 'plantTypes']);
        });
    }

    public function canViewProfile(User $viewer, Model $profile): bool
    {
        if (! $profile instanceof EngineerProfile) {
            throw new RuntimeException('Unsupported profile type.');
        }

        if ((int) $profile->user_id === (int) $viewer->id || $viewer->role === 'admin') {
            return true;
        }

        if (! $profile->is_discoverable) {
            return false;
        }

        $privacy = $profile->privacy_settings ?? [];

        return ($privacy['show_activity_feed'] ?? true) !== false;
    }

    public function visibleEngineerProfiles(?User $viewer = null)
    {
        $query = EngineerProfile::query()->discoverable()->with(['user', 'plantTypes']);

        if ($viewer?->role !== 'admin') {
            $query->whereHas('user', function ($query): void {
                $query->whereIn('role', ['unverified_member', 'professional']);
            });
        }

        return $query;
    }

    private function ensureActiveUser(User $user): void
    {
        if ($user->status !== 'active') {
            throw new RuntimeException('Only active users can manage profiles.');
        }
    }

    private function syncProfilePlantTypes(EngineerProfile $profile, array $data): void
    {
        if (! array_key_exists('plant_type_ids', $data) && ! array_key_exists('primary_plant_type_id', $data)) {
            return;
        }

        $plantTypeIds = array_values(array_unique(array_map(
            static fn ($plantTypeId): int => (int) $plantTypeId,
            $data['plant_type_ids'] ?? []
        )));

        $primaryPlantTypeId = isset($data['primary_plant_type_id']) ? (int) $data['primary_plant_type_id'] : null;

        if ($primaryPlantTypeId !== null && ! in_array($primaryPlantTypeId, $plantTypeIds, true)) {
            $plantTypeIds[] = $primaryPlantTypeId;
        }

        $activePlantTypeIds = PlantType::query()
            ->active()
            ->whereIn('id', $plantTypeIds)
            ->pluck('id')
            ->map(fn (int|string $plantTypeId): int => (int) $plantTypeId)
            ->all();

        $plantTypeIds = array_values(array_filter($plantTypeIds, fn (int $plantTypeId): bool => in_array($plantTypeId, $activePlantTypeIds, true)));

        if ($primaryPlantTypeId !== null && ! in_array($primaryPlantTypeId, $plantTypeIds, true)) {
            $primaryPlantTypeId = $plantTypeIds[0] ?? null;
        }

        $syncPayload = [];

        foreach ($plantTypeIds as $sortOrder => $plantTypeId) {
            $syncPayload[$plantTypeId] = [
                'is_primary' => $primaryPlantTypeId !== null && $plantTypeId === $primaryPlantTypeId,
                'sort_order' => $sortOrder,
            ];
        }

        $profile->plantTypes()->sync($syncPayload);
    }

    private function filterEngineerData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'photo_media_id',
            'bio',
            'current_company',
            'current_institution',
            'position',
            'field_of_study',
            'plant_name',
            'experience_years',
            'education',
            'expertise_tags',
            'industry_specialization',
            'searchable_keywords',
            'references',
            'phone',
            'linkedin_url',
            'job_availability',
            'reputation_points',
            'reputation_breakdown',
            'ai_usage_count',
            'is_discoverable',
            'privacy_settings',
            'notification_preferences',
            'verification_intent',
            'verification_document_media_id',
            'verification_renewed_at',
            'renewal_reminder_sent_at',
        ]));
    }
}
