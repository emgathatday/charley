<?php

namespace App\Policies;

use App\Models\PartnerPresentation;
use App\Models\User;

class PartnerPresentationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function view(User $user, PartnerPresentation $partnerPresentation): bool
    {
        return $user->status === 'active'
            && ($partnerPresentation->status === 'approved' || $this->canManagePartner($user, $partnerPresentation));
    }

    public function update(User $user, PartnerPresentation $partnerPresentation): bool
    {
        return $this->canManagePartner($user, $partnerPresentation);
    }

    public function delete(User $user, PartnerPresentation $partnerPresentation): bool
    {
        return $this->update($user, $partnerPresentation);
    }

    public function download(User $user, PartnerPresentation $partnerPresentation): bool
    {
        return $this->view($user, $partnerPresentation)
            && ($partnerPresentation->download_allowed || $this->canManagePartner($user, $partnerPresentation));
    }

    public function moderate(User $user, PartnerPresentation $partnerPresentation): bool
    {
        return false;
    }

    private function canManagePartner(User $user, PartnerPresentation $partnerPresentation): bool
    {
        $partnerProfile = $partnerPresentation->partner;

        return $user->status === 'active'
            && $partnerProfile !== null
            && ((int) $partnerProfile->user_id === (int) $user->id
                || $partnerProfile->members()
                    ->where('user_id', $user->id)
                    ->where('member_role', 'manager')
                    ->where('status', 'active')
                    ->exists());
    }
}
