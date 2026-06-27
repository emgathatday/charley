<?php

namespace App\Services;

use App\Models\EngineerProfile;
use App\Models\UnverifiedMemberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProfileService
{
    public function __construct(
        private readonly ProfileSearchIndexService $searchIndexService
    ) {
    }

    public function upsertEngineerProfile(User $user, array $data): EngineerProfile
    {
        $this->ensureActiveUser($user);

        if ($user->role !== 'professional') {
            throw new RuntimeException('Only professional users can have engineer profiles.');
        }

        return DB::transaction(function () use ($user, $data): EngineerProfile {
            $profile = EngineerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $this->filterEngineerData($data)
            );

            $this->searchIndexService->refresh($profile);

            return $profile;
        });
    }

    public function upsertUnverifiedMemberProfile(User $user, array $data): UnverifiedMemberProfile
    {
        $this->ensureActiveUser($user);

        if ($user->role !== 'unverified_member') {
            throw new RuntimeException('Only unverified members can have unverified member profiles.');
        }

        return DB::transaction(function () use ($user, $data): UnverifiedMemberProfile {
            $profile = UnverifiedMemberProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $this->filterUnverifiedData($data)
            );

            $this->searchIndexService->refresh($profile);

            return $profile;
        });
    }

    public function canViewProfile(User $viewer, Model $profile): bool
    {
        if (! $profile instanceof EngineerProfile && ! $profile instanceof UnverifiedMemberProfile) {
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
        $query = EngineerProfile::query()->discoverable()->with('user');

        if ($viewer?->role !== 'admin') {
            $query->where('is_discoverable', true);
        }

        return $query;
    }

    private function ensureActiveUser(User $user): void
    {
        if ($user->status !== 'active') {
            throw new RuntimeException('Only active users can manage profiles.');
        }
    }

    private function filterEngineerData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'photo_media_id',
            'bio',
            'current_company',
            'position',
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
            'verification_document_media_id',
            'verification_renewed_at',
            'renewal_reminder_sent_at',
        ]));
    }

    private function filterUnverifiedData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'photo_media_id',
            'bio',
            'current_institution',
            'field_of_study',
            'experience_years',
            'education',
            'references',
            'expertise_tags',
            'searchable_keywords',
            'is_discoverable',
            'privacy_settings',
            'notification_preferences',
            'linkedin_url',
            'job_availability',
            'verification_intent',
        ]));
    }
}
