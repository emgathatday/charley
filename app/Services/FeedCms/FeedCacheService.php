<?php

namespace App\Services\FeedCms;

use App\Models\User;
use App\Models\UserFeedCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedCacheService
{
    public function __construct(private readonly FeedPriorityService $priorities)
    {
    }

    public function personalizedFeed(User $user, int $limit = 20): Collection
    {
        return UserFeedCache::query()
            ->with('feedable')
            ->whereBelongsTo($user)
            ->fresh()
            ->orderByDesc('priority_score')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function rebuild(User $user, iterable $items, int $ttlMinutes = 10080): Collection
    {
        return DB::transaction(function () use ($user, $items, $ttlMinutes): Collection {
            UserFeedCache::query()->whereBelongsTo($user)->delete();

            $records = collect($items)->map(function (array $item) use ($user, $ttlMinutes): UserFeedCache {
                $feedable = $item['feedable'] ?? null;

                if (! $feedable instanceof Model || ! $feedable->exists) {
                    throw ValidationException::withMessages([
                        'feedable' => 'Feed cache items must include a persisted model.',
                    ]);
                }

                return UserFeedCache::query()->create([
                    'user_id' => $user->id,
                    'feedable_type' => $feedable::class,
                    'feedable_id' => $feedable->getKey(),
                    'priority_score' => $this->priorities->score($item['content_type'] ?? 'network_post', $item['base_score'] ?? 0),
                    'source_reason' => $item['source_reason'] ?? 'fresh_content',
                    'is_seen' => false,
                    'created_at' => now(),
                    'expires_at' => now()->addMinutes($ttlMinutes),
                ]);
            });

            return $records->values();
        });
    }

    public function markSeen(UserFeedCache $cache): UserFeedCache
    {
        $cache->forceFill(['is_seen' => true])->save();

        return $cache->refresh();
    }

    public function expireStale(): int
    {
        return UserFeedCache::query()->expired()->delete();
    }
}
