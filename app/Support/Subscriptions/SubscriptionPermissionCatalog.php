<?php

namespace App\Support\Subscriptions;

use Illuminate\Support\Collection;

class SubscriptionPermissionCatalog
{
    public const KEYS = [
        'announcements.create',
        'events.publish',
        'webinars.host',
        'polls.create',
        'messages.initiate',
        'jobs.create',
        'ai.use',
    ];

    public function all(): Collection
    {
        return collect([
            'announcements.create' => ['key' => 'announcements.create', 'name' => 'Create announcements', 'module' => 'posts', 'value_type' => 'integer', 'default_value' => 0, 'description' => 'Monthly announcement quota.', 'is_active' => true],
            'events.publish' => ['key' => 'events.publish', 'name' => 'Publish events', 'module' => 'events', 'value_type' => 'boolean', 'default_value' => false, 'description' => 'Publish partner events.', 'is_active' => true],
            'webinars.host' => ['key' => 'webinars.host', 'name' => 'Host webinars', 'module' => 'events', 'value_type' => 'boolean', 'default_value' => false, 'description' => 'Host webinars on the platform.', 'is_active' => true],
            'polls.create' => ['key' => 'polls.create', 'name' => 'Create polls', 'module' => 'polls', 'value_type' => 'boolean', 'default_value' => false, 'description' => 'Create technical polls.', 'is_active' => true],
            'messages.initiate' => ['key' => 'messages.initiate', 'name' => 'Initiate messages', 'module' => 'messaging', 'value_type' => 'boolean', 'default_value' => false, 'description' => 'Start messages with professionals.', 'is_active' => true],
            'jobs.create' => ['key' => 'jobs.create', 'name' => 'Create jobs', 'module' => 'jobs', 'value_type' => 'integer', 'default_value' => 0, 'description' => 'Monthly job posting quota.', 'is_active' => true],
            'ai.use' => ['key' => 'ai.use', 'name' => 'Use AI assistant', 'module' => 'ai-assistant', 'value_type' => 'integer', 'default_value' => 0, 'description' => 'Monthly AI usage quota.', 'is_active' => true],
        ]);
    }

    public function active(): Collection
    {
        return $this->all()->where('is_active', true)->values();
    }
}
