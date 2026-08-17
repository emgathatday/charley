<?php

namespace App\Policies;

use App\Models\PartnerMember;
use App\Models\User;

class PartnerMemberPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function view(User $user, PartnerMember $partnerMember): bool
    {
        return $user->status === 'active' && $this->belongsToPartner($user, $partnerMember);
    }

    public function update(User $user, PartnerMember $partnerMember): bool
    {
        return $this->canManagePartner($user, $partnerMember);
    }

    public function delete(User $user, PartnerMember $partnerMember): bool
    {
        return $this->update($user, $partnerMember);
    }

    private function belongsToPartner(User $user, PartnerMember $partnerMember): bool
    {
        $partnerProfile = $partnerMember->partner;

        return $partnerProfile !== null
            && ((int) $partnerMember->user_id === (int) $user->id
                || (int) $partnerProfile->user_id === (int) $user->id
                || $partnerProfile->members()
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->exists());
    }

    private function canManagePartner(User $user, PartnerMember $partnerMember): bool
    {
        $partnerProfile = $partnerMember->partner;

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
