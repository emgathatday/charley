<?php

namespace App\Services\Qa\Moderation;

interface QaModerationProvider
{
    public function check(array $payload): ?array;
}
