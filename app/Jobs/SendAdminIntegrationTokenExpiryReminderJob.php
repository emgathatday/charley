<?php

namespace App\Jobs;

use App\Models\AdminIntegration;
use App\Notifications\AdminIntegrationTokenExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAdminIntegrationTokenExpiryReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int $integrationId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $integration = AdminIntegration::query()->with('user')->find($this->integrationId);

        if (! $integration) {
            $this->fail('Admin integration no longer exists.');

            return;
        }

        if (! $integration->user) {
            $this->fail('Admin integration reminder has no admin user.');

            return;
        }

        $integration->user->notify(new AdminIntegrationTokenExpiringNotification($integration));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Admin integration token reminder failed.', [
            'job' => self::class,
            'integration_id' => $this->integrationId,
            'message' => $exception->getMessage(),
        ]);
    }
}
