<?php

namespace Tests\Unit;

use App\Models\SubscriptionPermission;
use App\Services\SubscriptionPermissionProvider;
use App\Support\Subscriptions\SubscriptionPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPermissionProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_exposes_stable_permission_metadata(): void
    {
        $catalog = app(SubscriptionPermissionCatalog::class)->all();

        $this->assertSame(SubscriptionPermissionCatalog::KEYS, $catalog->keys()->values()->all());
        $this->assertSame('integer', $catalog->get('announcements.create')['value_type']);
        $this->assertSame(false, $catalog->get('webinars.host')['default_value']);
        $this->assertTrue($catalog->every(fn (array $permission): bool => array_key_exists('description', $permission) && $permission['is_active'] === true));
    }

    public function test_database_provider_reads_existing_rows_without_deleting_data(): void
    {
        config(['subscriptions.permission_source' => 'database']);
        $existing = SubscriptionPermission::query()->create([
            'key' => 'custom.existing',
            'name' => 'Existing custom permission',
            'module' => 'custom',
            'value_type' => 'string',
            'default_value' => 'allowed',
            'is_active' => true,
        ]);

        $permissions = app(SubscriptionPermissionProvider::class)->active();

        $this->assertTrue($permissions->contains('id', $existing->id));
        $this->assertDatabaseHas('subscription_permissions', ['id' => $existing->id, 'key' => 'custom.existing']);
    }

    public function test_catalog_provider_uses_stable_catalog_and_preserves_database_rows(): void
    {
        config(['subscriptions.permission_source' => 'catalog']);
        $databasePermission = SubscriptionPermission::query()->create([
            'key' => 'announcements.create',
            'name' => 'Database label',
            'module' => 'legacy',
            'value_type' => 'string',
            'default_value' => 'legacy',
            'is_active' => true,
        ]);
        $extraPermission = SubscriptionPermission::query()->create([
            'key' => 'custom.keep',
            'name' => 'Keep me',
            'module' => 'custom',
            'value_type' => 'boolean',
            'default_value' => false,
            'is_active' => true,
        ]);

        $permissions = app(SubscriptionPermissionProvider::class)->active();
        $announcement = $permissions->firstWhere('key', 'announcements.create');

        $this->assertSame($databasePermission->id, $announcement->id);
        $this->assertSame('Create announcements', $announcement->name);
        $this->assertFalse($permissions->contains('key', 'custom.keep'));
        $this->assertDatabaseHas('subscription_permissions', ['id' => $extraPermission->id, 'key' => 'custom.keep']);
    }
}
