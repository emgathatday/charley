<?php

namespace App\Services\Admin;

use App\Models\PlatformSetting;
use InvalidArgumentException;

class PlatformSettingService
{
    public function __construct(private readonly PlatformSetting $settings) {}

    public function set(string $key, string $value, string $group, ?string $description = null): PlatformSetting
    {
        if ($key === '' || $group === '') {
            throw new InvalidArgumentException('Platform setting key and group are required.');
        }

        return $this->settings->newQuery()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'description' => $description]
        );
    }

    public function value(string $key, ?string $default = null): ?string
    {
        return $this->settings->newQuery()->where('key', $key)->value('value') ?? $default;
    }

    public function group(string $group)
    {
        return $this->settings->newQuery()->where('group', $group)->orderBy('key')->get();
    }
}
