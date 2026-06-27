<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMeta;

class UserMetaService
{
    public function set(User $user, string $key, ?string $value): UserMeta
    {
        return UserMeta::updateOrCreate(
            [
                'user_id' => $user->id,
                'key' => $key,
            ],
            [
                'value' => $value,
            ]
        );
    }
}
