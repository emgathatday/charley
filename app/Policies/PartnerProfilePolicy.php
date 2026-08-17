<?php

namespace App\Policies;

use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PartnerProfilePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isActive($user);
    }

    public function view(User $user, PartnerProfile $partnerProfile): bool
    {
        return $this->isActive($user)
            && ($this->belongsToPartner($user, $partnerProfile) || $partnerProfile->approval_status === 'approved');
    }

    public function create(User $user): bool
    {
        return $this->isActive($user)
            && $user->role === 'partner'
            && ! PartnerProfile::query()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, PartnerProfile $partnerProfile): bool|Response
    {
        if (! $this->isActive($user)) {
            return Response::deny('Only active users can update partner profiles.');
        }

        return $this->isOwner($user, $partnerProfile) || $this->isPartnerManager($user, $partnerProfile);
    }

    public function delete(User $user, PartnerProfile $partnerProfile): bool
    {
        return false;
    }

    public function approve(User $user, PartnerProfile $partnerProfile): bool
    {
        return false;
    }

    public function reject(User $user, PartnerProfile $partnerProfile): bool
    {
        return false;
    }

    public function suspend(User $user, PartnerProfile $partnerProfile): bool
    {
        return false;
    }

    public function manageMembers(User $user, PartnerProfile $partnerProfile): bool
    {
        return $this->isActive($user)
            && ($this->isOwner($user, $partnerProfile) || $this->isPartnerManager($user, $partnerProfile));
    }

    public function manageProducts(User $user, PartnerProfile $partnerProfile): bool
    {
        return $this->manageMembers($user, $partnerProfile);
    }

    public function managePresentations(User $user, PartnerProfile $partnerProfile): bool
    {
        return $this->manageMembers($user, $partnerProfile);
    }

    private function belongsToPartner(User $user, PartnerProfile $partnerProfile): bool
    {
        return $this->isOwner($user, $partnerProfile)
            || $partnerProfile->members()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
    }

    private function isOwner(User $user, PartnerProfile $partnerProfile): bool
    {
        return (int) $partnerProfile->user_id === (int) $user->id;
    }

    private function isPartnerManager(User $user, PartnerProfile $partnerProfile): bool
    {
        return $partnerProfile->members()
            ->where('user_id', $user->id)
            ->where('member_role', 'manager')
            ->where('status', 'active')
            ->exists();
    }

    private function isActive(User $user): bool
    {
        return $user->status === 'active';
    }
}
