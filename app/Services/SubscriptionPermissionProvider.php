<?php

namespace App\Services;

use App\Models\SubscriptionPermission;
use App\Support\Subscriptions\SubscriptionPermissionCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SubscriptionPermissionProvider
{
    public function __construct(private readonly SubscriptionPermissionCatalog $catalog)
    {
    }

    public function active(?string $source = null): Collection
    {
        return $this->forSource($source)->where('is_active', true)->values();
    }

    public function all(?string $source = null): Collection
    {
        return $this->forSource($source);
    }

    public function source(): string
    {
        $source = (string) config('subscriptions.permission_source', 'database');

        return in_array($source, ['database', 'catalog'], true) ? $source : 'database';
    }

    private function forSource(?string $source): Collection
    {
        return match ($source ?? $this->source()) {
            'catalog' => $this->catalogPermissions(),
            default => $this->databasePermissions(),
        };
    }

    private function databasePermissions(): Collection
    {
        if (! Schema::hasTable('subscription_permissions')) {
            return collect();
        }

        return SubscriptionPermission::query()
            ->orderBy('module')
            ->orderBy('key')
            ->get();
    }

    private function catalogPermissions(): Collection
    {
        $databasePermissions = $this->databasePermissions()->keyBy('key');

        return $this->catalog->all()
            ->map(function (array $definition) use ($databasePermissions) {
                $permission = $databasePermissions->get($definition['key']);

                if ($permission) {
                    $permission->forceFill($definition);

                    return $permission;
                }

                $permission = new SubscriptionPermission();
                $permission->forceFill($definition + ['id' => $definition['key']]);

                return $permission;
            })
            ->values();
    }
}
