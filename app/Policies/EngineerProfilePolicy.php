<?php

namespace App\Policies;

use App\Models\Connection;
use App\Models\EngineerProfile;
use App\Models\User;

class EngineerProfilePolicy
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

    public function view(User $user, EngineerProfile $engineerProfile): bool
    {
        if ($this->ownsProfile($user, $engineerProfile)) {
            return $this->isActive($user);
        }

        return $this->viewAny($user) && (bool) $engineerProfile->is_discoverable;
    }

    public function create(User $user): bool
    {
        if (! $this->isVerifiedProfessional($user)) {
            return false;
        }

        return ! EngineerProfile::query()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, EngineerProfile $engineerProfile): bool
    {
        return $this->ownsProfile($user, $engineerProfile)
            && $this->isVerifiedProfessional($user);
    }

    public function delete(User $user, EngineerProfile $engineerProfile): bool
    {
        return $this->update($user, $engineerProfile);
    }

    public function viewContact(User $user, EngineerProfile $engineerProfile): bool
    {
        if (! $this->view($user, $engineerProfile)) {
            return false;
        }

        if ($this->ownsProfile($user, $engineerProfile)) {
            return true;
        }

        return $this->allowsPrivacy($engineerProfile->privacy_settings, 'contact_visibility', $user, $engineerProfile->user_id);
    }

    public function viewAdminReview(User $user, EngineerProfile $engineerProfile): bool
    {
        return $this->ownsProfile($user, $engineerProfile)
            && $this->isVerifiedProfessional($user);
    }

    private function ownsProfile(User $user, EngineerProfile $engineerProfile): bool
    {
        return (int) $engineerProfile->user_id === (int) $user->id;
    }

    private function isActive(User $user): bool
    {
        return $user->status === 'active';
    }

    private function isVerifiedProfessional(User $user): bool
    {
        return $this->isActive($user)
            && $user->role === 'professional'
            && (bool) $user->is_verified;
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
