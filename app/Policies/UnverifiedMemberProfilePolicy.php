<?php

namespace App\Policies;

use App\Models\Connection;
use App\Models\UnverifiedMemberProfile;
use App\Models\User;

class UnverifiedMemberProfilePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isActive($user) && in_array($user->role, [
            'professional',
            'partner',
            'unverified_member',
        ], true);
    }

    public function view(User $user, UnverifiedMemberProfile $unverifiedMemberProfile): bool
    {
        if ($this->ownsProfile($user, $unverifiedMemberProfile)) {
            return $this->isActive($user);
        }

        return $this->viewAny($user)
            && in_array($user->role, ['professional', 'partner'], true)
            && (bool) $unverifiedMemberProfile->is_discoverable;
    }

    public function create(User $user): bool
    {
        if (! $this->isUnverifiedMember($user)) {
            return false;
        }

        return ! UnverifiedMemberProfile::query()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, UnverifiedMemberProfile $unverifiedMemberProfile): bool
    {
        return $this->ownsProfile($user, $unverifiedMemberProfile)
            && $this->isUnverifiedMember($user);
    }

    public function delete(User $user, UnverifiedMemberProfile $unverifiedMemberProfile): bool
    {
        return $this->update($user, $unverifiedMemberProfile);
    }

    public function requestVerification(User $user, UnverifiedMemberProfile $unverifiedMemberProfile): bool
    {
        return $this->update($user, $unverifiedMemberProfile)
            && ! (bool) $unverifiedMemberProfile->verification_intent;
    }

    public function viewContact(User $user, UnverifiedMemberProfile $unverifiedMemberProfile): bool
    {
        if (! $this->view($user, $unverifiedMemberProfile)) {
            return false;
        }

        if ($this->ownsProfile($user, $unverifiedMemberProfile)) {
            return true;
        }

        return $this->allowsPrivacy($unverifiedMemberProfile->privacy_settings, 'contact_visibility', $user, $unverifiedMemberProfile->user_id);
    }

    private function ownsProfile(User $user, UnverifiedMemberProfile $unverifiedMemberProfile): bool
    {
        return (int) $unverifiedMemberProfile->user_id === (int) $user->id;
    }

    private function isActive(User $user): bool
    {
        return $user->status === 'active';
    }

    private function isUnverifiedMember(User $user): bool
    {
        return $this->isActive($user) && $user->role === 'unverified_member';
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    private function allowsPrivacy(?array $settings, string $key, User $viewer, int $ownerId): bool
    {
        $visibility = $settings[$key] ?? 'private';

        if ($visibility === 'public') {
            return true;
        }

        if ($visibility === 'connections_only') {
            return Connection::query()
                ->where('status', 'accepted')
                ->where(function ($query) use ($viewer, $ownerId): void {
                    $query->where(function ($query) use ($viewer, $ownerId): void {
                        $query->where('requester_id', $viewer->id)
                            ->where('receiver_id', $ownerId);
                    })->orWhere(function ($query) use ($viewer, $ownerId): void {
                        $query->where('requester_id', $ownerId)
                            ->where('receiver_id', $viewer->id);
                    });
                })
                ->exists();
        }

        return false;
    }
}
