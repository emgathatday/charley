<?php

namespace App\Services\Admin;

use App\Models\AdminIntegration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminIntegrationService
{
    public function __construct(private readonly AdminIntegration $integrations) {}

    public function connect(User $admin, array $data): AdminIntegration
    {
        $provider = $data['provider'] ?? null;

        if (! in_array($provider, ['outlook', 'gmail'], true)) {
            throw new InvalidArgumentException('Invalid admin integration provider.');
        }

        return DB::transaction(function () use ($admin, $data, $provider): AdminIntegration {
            return $this->integrations->newQuery()->updateOrCreate(
                ['user_id' => $admin->id, 'provider' => $provider],
                [
                    'access_token' => $data['access_token'] ?? throw new InvalidArgumentException('Access token is required.'),
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'token_expires_at' => $data['token_expires_at'] ?? throw new InvalidArgumentException('Token expiry is required.'),
                    'config_metadata' => $data['config_metadata'] ?? null,
                ]
            );
        });
    }

    public function disconnect(AdminIntegration $integration): void
    {
        $integration->delete();
    }

    public function expired()
    {
        return $this->integrations->newQuery()->expired()->get();
    }
}
