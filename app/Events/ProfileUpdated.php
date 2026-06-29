<?php

namespace App\Events;

use App\Models\EngineerProfile;
use App\Models\UnverifiedMemberProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfileUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param class-string<EngineerProfile|UnverifiedMemberProfile> $profileClass
     */
    public function __construct(
        public readonly string $profileClass,
        public readonly int $profileId,
        public readonly array $changedFields = [],
    ) {
    }
}