<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SocialAccountService
{
    public function link(User $user, string $providerName, string $providerId): SocialAccount
    {
        if (! in_array($providerName, ['google', 'apple', 'linkedin'], true)) {
            throw new RuntimeException('Unsupported social provider.');
        }

        return DB::transaction(function () use ($user, $providerName, $providerId): SocialAccount {
            return SocialAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider_name' => $providerName,
                ],
                [
                    'provider_id' => $providerId,
                    'is_active' => true,
                ]
            );
        });
    }

    public function unlink(SocialAccount $socialAccount): SocialAccount
    {
        $socialAccount->forceFill(['is_active' => false])->save();

        return $socialAccount;
    }
}
