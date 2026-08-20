<?php

namespace App\DataTransferObjects\Iam;

use App\Models\User;

readonly class EngineerAccountResult
{
    public function __construct(public User $user)
    {
    }
}
