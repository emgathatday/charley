<?php

namespace App\Services\Admin;

use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class IamUserDetailViewDataService
{
    public function payload(User $user): array
    {
        $profile = $this->profileFor($user);
        $detail = [
            'id' => (string) $user->id,
            'name' => $this->displayName($user),
            'email' => $user->email,
            'username' => $user->username ?: 'Not set',
            'role' => str_replace('_', ' ', $user->role),
            'status' => $this->statusLabel($user),
            'verification' => $this->verificationSummary($user),
            'mfa' => $user->mfa_enabled ? 'Enabled' : 'Disabled',
            'login_attempts' => (string) $user->login_attempts,
            'last_login' => $user->last_login_at?->format('Y-m-d H:i') ?? 'Never',
            'locked_until' => $user->locked_until?->format('Y-m-d H:i') ?? 'Not locked',
            'joined' => $user->created_at?->format('M j, Y') ?? 'Unknown',
            'verified_at' => $user->verified_at?->format('M j, Y') ?? 'Not verified',
            'verification_due' => $user->verification_expires_at?->format('M j, Y') ?? 'Not scheduled',
            'profile' => $profile,
            'specialty' => $this->profileSpecialty($user, $profile),
            'experience' => $this->profileExperience($user, $profile),
            'plant_focus' => $this->profilePlantFocus($user, $profile),
            'security' => $this->securityDetail($user),
            'activity' => $this->activityDetail($user),
            'metas' => $this->metaDetail($user),
        ];

        $view = match ($user->role) {
            'partner' => 'iam.users.show-partner',
            'admin', 'moderator' => 'iam.users.show-admin',
            default => 'iam.users.show-engineer',
        };

        return [
            'view' => $view,
            'data' => [
                'user' => $user,
                'detail' => $detail,
                'partnerLogoUrl' => $user->role === 'partner' ? $this->partnerLogoUrl($profile) : null,
                'profilePhotoUrl' => in_array($user->role, ['professional', 'unverified_member'], true) ? $this->profilePhotoUrl($profile) : null,
            ],
        ];
    }

    private function displayName(User $user): string
    {
        $name = trim(implode(' ', array_filter([$user->first_name, $user->last_name])));

        return $name !== '' ? $name : ($user->username ?: $user->email);
    }

    private function profileFor(User $user): ?object
    {
        if ($user->role === 'partner' && Schema::hasTable('partner_profiles')) {
            $relations = [];
            if (Schema::hasTable('media_files')) {
                $relations[] = 'logoMedia';
            }
            if (Schema::hasTable('plant_types')) {
                $relations[] = 'plantType';
            }
            if (Schema::hasTable('partner_subscriptions') && Schema::hasTable('subscription_tiers')) {
                $relations[] = 'activePartnerSubscription.tier';
            }
            if (Schema::hasTable('partner_profile_plant_type') && Schema::hasTable('plant_types')) {
                $relations[] = 'plantTypes';
            }

            $profile = PartnerProfile::query()->with($relations)->where('user_id', $user->id)->first();
            if ($profile) {
                $activeSubscription = $profile->activePartnerSubscription;
                if (! $activeSubscription && Schema::hasTable('partner_subscriptions')) {
                    $activeSubscription = PartnerSubscription::query()->with('tier')->active()->where('user_id', $user->id)->latest('starts_at')->latest('id')->first();
                }

                $profile->setAttribute('active_subscription', $activeSubscription);
                $profile->setAttribute('partner_tier', $activeSubscription?->tier?->display_name ?? ($profile->partner_tier ?? 'No active tier'));
            }

            return $profile;
        }

        return match ($user->role) {
            'professional' => Schema::hasTable('engineer_profiles') ? DB::table('engineer_profiles')->where('user_id', $user->id)->first() : null,
            'unverified_member' => Schema::hasTable('engineer_profiles')
                ? DB::table('engineer_profiles')->where('user_id', $user->id)->first()
                : (Schema::hasTable('unverified_member_profiles') ? DB::table('unverified_member_profiles')->where('user_id', $user->id)->first() : null),
            default => null,
        };
    }

    private function securityDetail(User $user): array
    {
        return [
            'active_sessions' => Schema::hasTable('sessions') ? $user->sessions()->count() : 0,
            'social_accounts' => Schema::hasTable('social_accounts') ? $user->socialAccounts()->count() : 0,
            'latest_ip' => 'Unknown',
        ];
    }

    private function activityDetail(User $user): array
    {
        $feed = Schema::hasTable('user_activity_feed') ? $user->activityFeed()->latest()->take(5)->get() : collect();
        $latestVerification = Schema::hasTable('verification_requests') ? $user->verificationRequests()->latest('id')->first() : null;

        return [
            'feed' => $feed,
            'feed_count' => $feed->count(),
            'verification_requests' => Schema::hasTable('verification_requests') ? $user->verificationRequests()->count() : 0,
            'pending_verifications' => Schema::hasTable('verification_requests') ? $user->verificationRequests()->where('status', 'pending')->count() : 0,
            'latest_verification' => $latestVerification,
        ];
    }

    private function metaDetail(User $user): array
    {
        if (! Schema::hasTable('user_metas')) {
            return [];
        }

        return $user->metas()->pluck('value', 'key')->all();
    }

    private function partnerLogoUrl(?object $profile): ?string
    {
        return $this->mediaUrl((int) ($profile->logo_media_id ?? 0));
    }

    private function profilePhotoUrl(?object $profile): ?string
    {
        return $this->mediaUrl((int) ($profile->photo_media_id ?? 0));
    }

    private function mediaUrl(int $mediaId): ?string
    {
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

    private function profilePlantFocus(User $user, ?object $profile): string
    {
        if (! $profile) {
            return 'No plant type';
        }

        if ($user->role === 'partner') {
            if ($profile instanceof PartnerProfile) {
                $plantTypes = $profile->relationLoaded('plantTypes') ? $profile->plantTypes->pluck('name') : collect();
                if ($plantTypes->isNotEmpty()) {
                    return $plantTypes->implode(', ');
                }

                if ($profile->relationLoaded('plantType') && $profile->plantType) {
                    return $profile->plantType->name;
                }
            }

            return $profile->plant_type_name ?? 'No plant type';
        }

        $pivotLabel = $this->engineerPlantTypeNames((int) ($profile->id ?? 0));

        return $pivotLabel
            ?: ($profile->plant_name ?? null)
            ?: $this->jsonLabel($profile->industry_specialization ?? null)
            ?: $this->jsonLabel($profile->expertise_tags ?? null)
            ?: ($profile->field_of_study ?? null)
            ?: 'No plant type';
    }

    private function engineerPlantTypeNames(int $profileId): ?string
    {
        if ($profileId <= 0 || ! Schema::hasTable('engineer_profile_plant_type') || ! Schema::hasTable('plant_types')) {
            return null;
        }

        $names = DB::table('engineer_profile_plant_type')
            ->join('plant_types', 'plant_types.id', '=', 'engineer_profile_plant_type.plant_type_id')
            ->where('engineer_profile_plant_type.engineer_profile_id', $profileId)
            ->orderByDesc('engineer_profile_plant_type.is_primary')
            ->orderBy('engineer_profile_plant_type.sort_order')
            ->orderBy('plant_types.name')
            ->pluck('plant_types.name')
            ->filter()
            ->values();

        return $names->isNotEmpty() ? $names->implode(', ') : null;
    }

    private function profileSpecialty(User $user, ?object $profile): string
    {
        if (! $profile) {
            return 'No profile yet';
        }

        return match ($user->role) {
            'professional' => $this->jsonLabel($profile->industry_specialization ?? null) ?: $this->jsonLabel($profile->expertise_tags ?? null) ?: 'No profile yet',
            'unverified_member' => ($profile->field_of_study ?? null) ?: $this->jsonLabel($profile->expertise_tags ?? null) ?: 'No profile yet',
            'partner' => $this->jsonLabel($profile->keywords ?? null) ?: 'No profile yet',
            default => 'No profile yet',
        };
    }

    private function profileExperience(User $user, ?object $profile): string
    {
        if (! $profile) {
            return 'No profile yet';
        }

        $years = match ($user->role) {
            'professional', 'unverified_member' => $profile->experience_years ?? null,
            default => null,
        };

        if ($years !== null && $years !== '') {
            return sprintf('%s years', $years);
        }

        if ($user->role === 'partner' && ($profile->founded_year ?? null)) {
            return sprintf('Founded %s', $profile->founded_year);
        }

        return 'No profile yet';
    }

    private function verificationSummary(User $user): string
    {
        $pending = Schema::hasTable('verification_requests') ? VerificationRequest::where('user_id', $user->id)->where('status', 'pending')->count() : 0;

        if ($pending > 0) {
            return sprintf('%s pending request%s', $pending, $pending === 1 ? '' : 's');
        }

        return $user->is_verified ? 'Verified' : 'Not verified';
    }

    private function statusLabel(User $user): string
    {
        return match ($user->status) {
            'active' => 'Active',
            'frozen' => 'Frozen',
            'suspended' => 'Suspended',
            default => ucfirst((string) $user->status),
        };
    }

    private function jsonLabel(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            $flat = collect($value)
                ->flatten()
                ->filter(fn ($item) => is_scalar($item) && trim((string) $item) !== '')
                ->map(fn ($item) => trim((string) $item))
                ->unique()
                ->take(3)
                ->values();

            return $flat->isNotEmpty() ? $flat->implode(', ') : null;
        }

        return trim((string) $value) ?: null;
    }
}
