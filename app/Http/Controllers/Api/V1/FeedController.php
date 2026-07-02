<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedCacheIndexRequest;
use App\Http\Resources\UserFeedCacheResource;
use App\Models\UserFeedCache;
use App\Services\FeedCms\FeedCacheService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    public function __construct(private readonly FeedCacheService $feedCache)
    {
    }

    public function index(FeedCacheIndexRequest $request): AnonymousResourceCollection
    {
        return UserFeedCacheResource::collection(
            $this->feedCache->personalizedFeed($request->user(), (int) ($request->validated()['limit'] ?? 20)),
        );
    }

    public function markSeen(FeedCacheIndexRequest $request, UserFeedCache $userFeedCache): UserFeedCacheResource
    {
        abort_unless($userFeedCache->user_id === $request->user()->id, 403);

        return new UserFeedCacheResource($this->feedCache->markSeen($userFeedCache));
    }
}
