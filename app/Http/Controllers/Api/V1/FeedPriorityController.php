<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedCmsAdminRequest;
use App\Http\Requests\FeedPriorityRequest;
use App\Http\Resources\HomepageFeedPriorityResource;
use App\Services\FeedCms\FeedPriorityService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedPriorityController extends Controller
{
    public function __construct(private readonly FeedPriorityService $priorities)
    {
    }

    public function index(FeedCmsAdminRequest $request): AnonymousResourceCollection
    {
        return HomepageFeedPriorityResource::collection($this->priorities->priorities());
    }

    public function update(FeedPriorityRequest $request): HomepageFeedPriorityResource
    {
        $data = $request->validated();

        return new HomepageFeedPriorityResource(
            $this->priorities->updatePriority($data['content_type'], $data, $request->user()),
        );
    }
}
