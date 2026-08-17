<?php

namespace App\Services\Admin;

use App\Models\MediaFile;
use App\Models\User;
use App\Queries\Admin\EngineerManagementQuery;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class EngineerManagementViewDataService
{
    public function __construct(private EngineerManagementQuery $users)
    {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function data(array $input = []): array
    {
        $filters = $this->users->filters($input);
        $usersQuery = $this->users->query($filters);
        $users = $usersQuery->latest('users.created_at')->paginate(10)->withQueryString();
        $users->getCollection()->each(fn (User $user) => $this->decorate($user));
        $stats = $this->users->stats();

        return [
            'users' => $users,
            'stats' => $stats,
            'filters' => $filters,
            'plantTypeOptions' => $this->users->plantTypeOptions(),
            'subscriptionTierOptions' => [],
            'tierStats' => [],
            'activeTab' => $filters['tab'],
            'engineerTabBar' => $this->tabBar($filters, $stats),
            'engineerStatCards' => $this->statCards($users, $stats),
            'engineerDefaultAvatar' => $this->defaultAvatar(),
        ];
    }

    private function decorate(User $user): void
    {
        $user->display_id = (string) $user->id;
        $user->display_name = $this->displayName($user);
        $user->plant_type_label = $this->plantTypeLabel($user);
        $user->status_label = $this->statusLabel($user);
        $user->status_class = in_array($user->status, ['active', 'pending', 'suspended', 'frozen'], true) ? $user->status : 'pending';
        $user->role_badge = $this->roleBadge($user);
        $user->expertise_badge = $this->expertiseBadge($user);
        $user->contribution_points = $this->contributionPoints($user);
        $user->filled_stars = $this->filledStars($user->contribution_points);

        $profilePhotoMediaId = match ($user->role) {
            'professional' => $user->engineer_photo_media_id ?? null,
            'unverified_member' => $user->unverified_photo_media_id ?? $user->engineer_photo_media_id ?? null,
            default => null,
        };
        $user->profile_photo_url = $profilePhotoMediaId ? $this->profilePhotoUrl((object) ['photo_media_id' => $profilePhotoMediaId]) : null;
    }

    private function tabBar(array $filters, array $stats): array
    {
        $activeTab = $filters['tab'] ?? 'all';
        $restrictedCount = ($stats['frozen_users'] ?? 0) + ($stats['suspended_users'] ?? 0);
        $tabs = [
            ['label' => 'All Users', 'tab' => 'all', 'count' => $stats['total_users'] ?? 0],
            ['label' => 'Professionals', 'tab' => 'professional', 'count' => $stats['professional_users'] ?? 0],
            ['label' => 'Registered Members', 'tab' => 'registered', 'count' => $stats['registered_members'] ?? 0],
            ['label' => 'Restricted', 'tab' => 'restricted', 'count' => $restrictedCount],
            ['label' => 'Frozen', 'tab' => 'frozen', 'count' => $stats['frozen_users'] ?? 0],
        ];

        return [
            'tabs' => collect($tabs)->map(fn (array $tab) => [
                'label' => $tab['label'],
                'count' => $tab['count'],
                'active' => $tab['tab'] === $activeTab,
                'href' => route('admin.dashboard.iam.users.engineers', [
                    'tab' => $tab['tab'],
                    'keyword' => $filters['keyword'] ?? '',
                    'account_type' => $filters['account_type'] ?? 'all',
                    'status' => $filters['status'] ?? 'all',
                    'plant_type_id' => $filters['plant_type_id'] ?? '',
                ]),
            ])->all(),
        ];
    }

    private function statCards($users, array $stats): array
    {
        $restrictedCount = ($stats['frozen_users'] ?? 0) + ($stats['suspended_users'] ?? 0);

        return [
            [
                'class' => 'blue',
                'label' => 'Total Users',
                'value' => number_format($stats['total_users'] ?? $users->total()),
                'sub' => 'All registered accounts',
                'chip' => ['class' => 'up', 'icon' => 'icon-month-professionals', 'label' => number_format($users->count()).' this page'],
            ],
            [
                'class' => 'indigo',
                'label' => 'Professionals',
                'value' => number_format($stats['professional_users'] ?? 0),
                'sub' => 'Active verified members',
                'chip' => ['class' => 'up', 'icon' => 'icon-month-professionals', 'label' => 'Active'],
            ],
            [
                'class' => 'amber',
                'label' => 'Pending Verification',
                'value' => number_format($stats['pending_approvals'] ?? 0),
                'sub' => 'Awaiting admin review',
                'chip' => ['class' => 'warn', 'icon' => 'icon-clock', 'label' => number_format($stats['pending_approvals'] ?? 0).' pending'],
            ],
            [
                'label' => 'Suspended / Frozen',
                'value' => number_format($restrictedCount),
                'sub' => 'Accounts restricted',
                'chip' => ['class' => 'red', 'icon' => 'icon-new-week', 'label' => number_format($stats['suspended_users'] ?? 0).' suspended'],
            ],
        ];
    }

    private function roleBadge(User $user): array
    {
        return $user->role === 'professional'
            ? ['label' => 'Professional', 'class' => 'professional']
            : ['label' => 'Registered Member', 'class' => 'member'];
    }

    private function expertiseBadge(User $user): array
    {
        if ($user->role !== 'professional') {
            return ['label' => 'Registered Member', 'class' => 'registered'];
        }

        $years = (int) ($user->engineer_experience_years ?? 0);

        if ($years >= 15) {
            return ['label' => 'Senior Industry Expert', 'class' => 'senior'];
        }

        if ($years >= 8) {
            return ['label' => 'Experienced Professional', 'class' => 'experienced'];
        }

        return ['label' => 'Industry Professional', 'class' => 'professional2'];
    }

    private function contributionPoints(User $user): int
    {
        return (($user->verification_requests_count ?? 0) * 45)
            + (($user->pending_verification_requests_count ?? 0) * 12)
            + ((int) ($user->engineer_experience_years ?? $user->unverified_experience_years ?? 0) * 120);
    }

    private function filledStars(int $points): int
    {
        return match (true) {
            $points >= 10000 => 5,
            $points >= 7500 => 4,
            $points >= 5000 => 3,
            $points >= 2500 => 2,
            $points >= 1000 => 1,
            default => 0,
        };
    }

    private function displayName(User $user): string
    {
        $name = trim(implode(' ', array_filter([$user->first_name, $user->last_name])));

        return $name !== '' ? $name : ($user->username ?: $user->email);
    }

    private function plantTypeLabel(User $user): string
    {
        return match ($user->role) {
            'professional' => $user->engineer_plant_type_names,
            'unverified_member' => $user->unverified_plant_type_names,
            default => null,
        } ?: 'No plant type';
    }

    private function statusLabel(User $user): string
    {
        return match ($user->status) {
            'active' => 'Active',
            'frozen' => 'Frozen',
            'suspended' => 'Suspended',
            default => ucfirst((string) $user->status),
        };
    }

    private function profilePhotoUrl(?object $profile): ?string
    {
        $mediaId = (int) ($profile->photo_media_id ?? 0);
        if ($mediaId <= 0 || ! Schema::hasTable('media_files')) {
            return null;
        }

        $media = MediaFile::query()->find($mediaId);
        if (! $media || ! $media->path) {
            return null;
        }

        try {
            return Storage::disk($media->disk ?: 'public')->url($media->path);
        } catch (\Throwable) {
            return null;
        }
    }

    private function defaultAvatar(): string
    {
        return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgcng9IjEwIiBmaWxsPSIjRjFGNEY4Ii8+PGNpcmNsZSBjeD0iNTAiIGN5PSIzOCIgcj0iMTgiIGZpbGw9IiNDOEQwREEiLz48cGF0aCBkPSJNNTAgNjBjLTIyIDAtMzQgMTQtMzQgMzJ2OGg2OHYtOGMwLTE4LTEyLTMyLTM0LTMyeiIgZmlsbD0iI0M4RDBEQSIvPjwvc3ZnPg==';
    }
}
