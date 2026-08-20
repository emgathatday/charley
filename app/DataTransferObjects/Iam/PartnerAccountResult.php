<?php

namespace App\DataTransferObjects\Iam;

use App\Models\PartnerSubscription;
use App\Models\User;

readonly class PartnerAccountResult
{
    public function __construct(public User $user, public ?PartnerSubscription $subscription)
    {
    }
}
