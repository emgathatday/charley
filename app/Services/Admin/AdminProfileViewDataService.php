<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AdminProfileViewDataService
{
    public function data(User $admin, string $currentSessionId): array
    {
        $sessions = Schema::hasTable('sessions')
            ? $admin->sessions()->orderByDesc('last_activity')->get()->each(function ($session) use ($currentSessionId): void {
                $session->is_current = hash_equals((string) $session->id, (string) $currentSessionId);
            })
            : collect();
        $latestSession = $sessions->firstWhere('is_current', true) ?? $sessions->first();

        return [
            'admin' => $admin,
            'displayName' => $this->displayName($admin),
            'initials' => 'AD',
            'profileTitle' => 'Platform Administrator',
            'organisation' => 'Charley Platform',
            'timezone' => config('app.timezone'),
            'sessions' => $sessions,
            'latestSession' => $latestSession,
        ];
    }

    public function sessionIdToPreserve(User $admin, string $currentSessionId): ?string
    {
        if (! Schema::hasTable('sessions')) {
            return null;
        }

        if ($admin->sessions()->whereKey($currentSessionId)->exists()) {
            return $currentSessionId;
        }

        return $admin->sessions()
            ->orderByDesc('last_activity')
            ->value('id');
    }

    private function displayName(User $user): string
    {
        $name = trim(implode(' ', array_filter([$user->first_name, $user->last_name])));

        return $name !== '' ? $name : ($user->username ?: $user->email);
    }
}
