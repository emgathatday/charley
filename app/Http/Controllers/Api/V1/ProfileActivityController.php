<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserMetaUpsertRequest;
use App\Http\Resources\UserActivityFeedResource;
use App\Http\Resources\UserMetaResource;
use App\Services\UserMetaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileActivityController extends Controller
{
    public function __construct(private readonly UserMetaService $userMetaService)
    {
    }

    public function activity(Request $request): AnonymousResourceCollection
    {
        return UserActivityFeedResource::collection(
            $request->user()->activityFeed()->latest('created_at')->paginate()
        );
    }

    public function metas(Request $request): AnonymousResourceCollection
    {
        return UserMetaResource::collection($request->user()->metas()->orderBy('key')->paginate());
    }

    public function upsertMeta(UserMetaUpsertRequest $request): UserMetaResource
    {
        $data = $request->validated();

        return new UserMetaResource($this->userMetaService->set($request->user(), $data['key'], $data['value'] ?? null));
    }
}
