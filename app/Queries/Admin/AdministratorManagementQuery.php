<?php

namespace App\Queries\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdministratorManagementQuery
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function filters(array $input): array
    {
        $filters = [
            'keyword' => trim((string) ($input['keyword'] ?? $input['search'] ?? '')),
            'status' => (string) ($input['status'] ?? 'all'),
            'tab' => (string) ($input['tab'] ?? 'all'),
            'member_view' => 'administrators',
            'plant_type_id' => '',
        ];

        if (! in_array($filters['tab'], ['all', 'active', 'pending', 'restricted', 'frozen', 'suspended'], true)) {
            $filters['tab'] = 'all';
        }

        if (! in_array($filters['status'], ['all', 'active', 'pending', 'suspended', 'frozen'], true)) {
            $filters['status'] = 'all';
        }

        return $filters;
    }

    public function query(array $filters): Builder
    {
        $query = $this->baseQuery();

        $this->applyKeyword($query, $filters['keyword']);
        $this->applyStatus($query, $filters['status']);
        $this->applyTab($query, $filters['tab']);

        return $query;
    }

    private function baseQuery(): Builder
    {
        return User::query()
            ->select('users.*')
            ->whereIn('users.role', ['admin', 'moderator']);
    }

    private function applyKeyword(Builder $query, string $keyword): void
    {
        if ($keyword === '') {
            return;
        }

        $keyword = strtolower($keyword);
        $query->where(function (Builder $query) use ($keyword): void {
            $query->whereRaw('lower(users.username) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(users.first_name) like ?', ["%{$keyword}%"])
                ->orWhereRaw('lower(users.last_name) like ?', ["%{$keyword}%"])
                ->orWhereRaw("lower(coalesce(users.first_name, '') || ' ' || coalesce(users.last_name, '')) like ?", ["%{$keyword}%"])
                ->orWhereRaw('lower(users.email) like ?', ["%{$keyword}%"]);
        });
    }

    private function applyStatus(Builder $query, string $status): void
    {
        if ($status === 'all') {
            return;
        }

        if ($status === 'pending') {
            $query->where('users.is_verified', false);
            return;
        }

        $query->where('users.status', $status);
    }

    private function applyTab(Builder $query, string $tab): void
    {
        match ($tab) {
            'active' => $query->where('users.status', 'active'),
            'pending' => $query->where('users.is_verified', false),
            'restricted' => $query->whereIn('users.status', ['suspended', 'frozen']),
            'frozen' => $query->where('users.status', 'frozen'),
            'suspended' => $query->where('users.status', 'suspended'),
            default => null,
        };
    }

    public function stats(): array
    {
        return [
            'total_users' => $this->baseQuery()->count('users.id'),
            'active_members' => $this->baseQuery()->where('users.status', 'active')->count('users.id'),
            'pending_approvals' => $this->baseQuery()->where('users.is_verified', false)->count('users.id'),
            'frozen_users' => $this->baseQuery()->where('users.status', 'frozen')->count('users.id'),
            'suspended_users' => $this->baseQuery()->where('users.status', 'suspended')->count('users.id'),
            'moderator_users' => $this->baseQuery()->where('users.role', 'moderator')->count('users.id'),
        ];
    }

}
