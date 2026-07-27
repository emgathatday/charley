<?php

namespace App\Policies;

use App\Models\PartnerProduct;
use App\Models\User;

class PartnerProductPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function view(User $user, PartnerProduct $partnerProduct): bool
    {
        return $user->status === 'active'
            && ($partnerProduct->is_active || $this->canManagePartner($user, $partnerProduct));
    }

    public function update(User $user, PartnerProduct $partnerProduct): bool
    {
        return $this->canManagePartner($user, $partnerProduct);
    }

    public function delete(User $user, PartnerProduct $partnerProduct): bool
    {
        return $this->update($user, $partnerProduct);
    }

    private function canManagePartner(User $user, PartnerProduct $partnerProduct): bool
    {
        $partnerProfile = $partnerProduct->partner;

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
