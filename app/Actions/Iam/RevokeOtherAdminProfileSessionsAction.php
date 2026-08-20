<?php

namespace App\Actions\Iam;

use App\DataTransferObjects\Iam\SessionRevocationResult;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RevokeOtherAdminProfileSessionsAction
{
    public function execute(User $admin, ?string $preservedSessionId): SessionRevocationResult
    {
        if (! Schema::hasTable('sessions')) {
            return new SessionRevocationResult(false);
        }

        $deleted = $admin->sessions()
            ->when($preservedSessionId, fn ($query) => $query->whereKeyNot($preservedSessionId))
            ->delete();

        if ($deleted > 0) {
            $admin->forceFill(['remember_token' => Str::random(60)])->save();
        }

        return new SessionRevocationResult($deleted > 0);
    }
}
