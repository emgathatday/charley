<?php

namespace App\Policies;

use App\Models\SearchIndexEntry;
use App\Models\User;

class SearchIndexEntryPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->status === 'active'
            && in_array($user->role, ['professional', 'partner', 'unverified_member'], true);
    }

    public function view(User $user, SearchIndexEntry $searchIndexEntry): bool
    {
        if (! $this->viewAny($user) || ! (bool) $searchIndexEntry->is_discoverable) {
            return false;
        }

        return match ($searchIndexEntry->search_context) {
            'expert_directory' => in_array($user->role, ['professional', 'partner', 'unverified_member'], true),
            'partner_directory' => in_array($user->role, ['professional', 'partner'], true),
            'global' => true,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SearchIndexEntry $searchIndexEntry): bool
    {
        return false;
    }

    public function delete(User $user, SearchIndexEntry $searchIndexEntry): bool
    {
        return false;
    }
}
