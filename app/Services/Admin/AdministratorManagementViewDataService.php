<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Queries\Admin\AdministratorManagementQuery;

class AdministratorManagementViewDataService
{
    public function __construct(private AdministratorManagementQuery $users)
    {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function data(array $input = []): array
    {
        $filters = $this->users->filters($input);
        $users = $this->users->query($filters)
            ->latest('users.created_at')
            ->paginate(10)
            ->withQueryString();
        $users->getCollection()->each(fn (User $user) => $this->decorate($user));
        $stats = $this->users->stats();

        return [
            'users' => $users,
            'stats' => $stats,
            'filters' => $filters,
            'memberView' => 'administrators',
            'pageTitle' => 'Administrator Management',
            'activeTab' => $filters['tab'],
            'adminStatCards' => $this->statCards($users, $stats),
            'adminTabBar' => $this->tabBar($filters, $stats),
        ];
    }

    private function decorate(User $user): void
    {
        $user->display_id = (string) $user->id;
        $user->display_name = $this->displayName($user);
        $user->initials = $this->initials($user->display_name);
        $user->role_label = str_replace('_', ' ', ucfirst((string) $user->role));
        $user->status_label = $this->statusLabel($user);
        $user->status_class = in_array($user->status, ['active', 'pending', 'suspended', 'frozen'], true) ? $user->status : 'pending';
        $user->role_badge = $this->roleBadge($user);
        $user->security_label = $this->securityLabel($user);
        $user->security_class = $user->mfa_enabled && ! $user->locked_until
            ? 'senior'
            : (((int) $user->login_attempts > 0 || $user->locked_until) ? 'registered' : 'professional2');
        $user->last_login_label = $user->last_login_at?->format('M j, Y H:i') ?? 'Never';
    }

    private function statCards($users, array $stats): array
    {
        $restrictedCount = ($stats['frozen_users'] ?? 0) + ($stats['suspended_users'] ?? 0);

        return [
            ['class' => 'blue', 'label' => 'Total Operators', 'value' => number_format($stats['total_users'] ?? $users->total()), 'sub' => 'Admin and moderator accounts', 'chip' => ['class' => 'up', 'label' => number_format($users->count()).' this page']],
            ['class' => 'indigo', 'label' => 'Active Operators', 'value' => number_format($stats['active_members'] ?? 0), 'sub' => 'Currently active access', 'chip' => ['class' => 'up', 'label' => 'Active']],
            ['class' => 'amber', 'label' => 'Moderators', 'value' => number_format($stats['moderator_users'] ?? 0), 'sub' => 'Scoped internal operators', 'chip' => ['class' => 'warn', 'label' => 'Role scoped']],
            ['label' => 'Restricted', 'value' => number_format($restrictedCount), 'sub' => 'Frozen or suspended operators', 'chip' => ['class' => 'red', 'label' => number_format($stats['suspended_users'] ?? 0).' suspended']],
        ];
    }

    private function tabBar(array $filters, array $stats): array
    {
        $activeTab = $filters['tab'] ?? 'all';
        $restrictedCount = ($stats['frozen_users'] ?? 0) + ($stats['suspended_users'] ?? 0);
        $tabs = [
            ['label' => 'All Operators', 'tab' => 'all', 'count' => $stats['total_users'] ?? 0],
            ['label' => 'Active', 'tab' => 'active', 'count' => $stats['active_members'] ?? 0],
            ['label' => 'Pending Review', 'tab' => 'pending', 'count' => $stats['pending_approvals'] ?? 0],
            ['label' => 'Restricted', 'tab' => 'restricted', 'count' => $restrictedCount],
        ];

        return [
            'tabs' => collect($tabs)->map(fn (array $tab) => [
                'label' => $tab['label'],
                'count' => $tab['count'],
                'active' => $tab['tab'] === $activeTab,
                'href' => route('admin.dashboard.iam.users', [
                    'tab' => $tab['tab'],
                    'keyword' => $filters['keyword'] ?? '',
                    'status' => $filters['status'] ?? 'all',
                ]),
            ])->all(),
        ];
    }

    private function displayName(User $user): string
    {
        $name = trim(implode(' ', array_filter([$user->first_name, $user->last_name])));

        return $name !== '' ? $name : ($user->username ?: $user->email);
    }

    private function initials(string $name): string
    {
        $initials = collect(explode(' ', trim($name)))
            ->filter()
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return $initials !== '' ? $initials : 'A';
    }

    private function roleBadge(User $user): array
    {
        return match ($user->role) {
            'admin' => ['label' => 'Admin', 'class' => 'professional'],
            'moderator' => ['label' => 'Moderator', 'class' => 'member'],
            default => ['label' => str_replace('_', ' ', ucfirst((string) $user->role)), 'class' => 'member'],
        };
    }

    private function securityLabel(User $user): string
    {
        $signals = [];
        $signals[] = $user->mfa_enabled ? 'MFA enabled' : 'MFA disabled';

        if ((int) $user->login_attempts > 0) {
            $signals[] = sprintf('%s failed login%s', $user->login_attempts, (int) $user->login_attempts === 1 ? '' : 's');
        }

        if ($user->locked_until !== null) {
            $signals[] = 'Locked';
        }

        return implode(' | ', $signals);
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
}
