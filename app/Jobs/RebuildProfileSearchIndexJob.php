<?php

namespace App\Jobs;

use App\Models\EngineerProfile;
use App\Models\UnverifiedMemberProfile;
use App\Services\ProfileSearchIndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class RebuildProfileSearchIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param class-string<EngineerProfile|UnverifiedMemberProfile>|null $profileClass
     */
    public function __construct(
        public readonly ?string $profileClass = null,
        public readonly ?int $profileId = null,
        public readonly array $contexts = ['expert_directory'],
    ) {
        $this->onQueue('search-index');
    }

    public function handle(ProfileSearchIndexService $service): void
    {
        if ($this->profileClass === null || $this->profileId === null) {
            $this->rebuildAll($service);

            return;
        }

        if (! in_array($this->profileClass, [EngineerProfile::class, UnverifiedMemberProfile::class], true)) {
            $this->fail(new InvalidArgumentException('Unsupported profile class for search indexing.'));

            return;
        }

        $profile = $this->profileClass::query()->find($this->profileId);

        if (! $profile instanceof Model) {
            $this->fail(new InvalidArgumentException('Profile not found for search indexing.'));

            return;
        }

        $this->refreshProfile($service, $profile);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Profile search index rebuild failed.', [
            'profile_class' => $this->profileClass,
            'profile_id' => $this->profileId,
            'message' => $exception->getMessage(),
        ]);
    }

    private function rebuildAll(ProfileSearchIndexService $service): void
    {
        EngineerProfile::query()
            ->select('id')
            ->chunkById(100, function ($profiles) use ($service): void {
                foreach ($profiles as $profile) {
                    $this->refreshProfile($service, EngineerProfile::query()->findOrFail($profile->id));
                }
            });

        UnverifiedMemberProfile::query()
            ->select('id')
            ->chunkById(100, function ($profiles) use ($service): void {
                foreach ($profiles as $profile) {
                    $this->refreshProfile($service, UnverifiedMemberProfile::query()->findOrFail($profile->id));
                }
            });
    }

    private function refreshProfile(ProfileSearchIndexService $service, Model $profile): void
    {
        foreach ($this->contexts as $context) {
            $service->refresh($profile, $context);
        }
    }
}