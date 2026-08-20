<?php

namespace App\Actions\Iam;

use App\DataTransferObjects\Iam\SessionRevocationResult;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RevokeAdminProfileSessionAction
{
    public function execute(User $admin, string $sessionId, ?string $preservedSessionId): SessionRevocationResult
    {
        if (! Schema::hasTable('sessions') || ($preservedSessionId && hash_equals((string) $preservedSessionId, $sessionId))) {
            return new SessionRevocationResult(false);
        }

        $deleted = $admin->sessions()->whereKey($sessionId)->delete();
        if ($deleted > 0) {
            $admin->forceFill(['remember_token' => Str::random(60)])->save();
        }

        return new SessionRevocationResult($deleted > 0);
    }
}
