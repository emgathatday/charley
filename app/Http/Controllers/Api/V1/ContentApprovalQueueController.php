<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAssignRequest;
use App\Http\Requests\AdminContentApprovalTransitionRequest;
use App\Http\Resources\ContentApprovalQueueResource;
use App\Models\ContentApprovalQueue;
use App\Models\User;
use App\Services\Admin\ContentApprovalQueueService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContentApprovalQueueController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdmin($request);

        return ContentApprovalQueueResource::collection(ContentApprovalQueue::query()->latest('submitted_at')->paginate($request->integer('per_page', 15)));
    }

    public function show(Request $request, ContentApprovalQueue $contentApprovalQueue): ContentApprovalQueueResource
    {
        $this->authorizeAdmin($request);

        return new ContentApprovalQueueResource($contentApprovalQueue);
    }

    public function assign(AdminAssignRequest $request, ContentApprovalQueue $contentApprovalQueue, ContentApprovalQueueService $service): ContentApprovalQueueResource
    {
        return new ContentApprovalQueueResource($service->assign($contentApprovalQueue, User::findOrFail($request->integer('admin_id'))));
    }

    public function approve(AdminContentApprovalTransitionRequest $request, ContentApprovalQueue $contentApprovalQueue, ContentApprovalQueueService $service): ContentApprovalQueueResource
    {
        return new ContentApprovalQueueResource($service->approve($contentApprovalQueue, $request->user(), $request->validated('admin_notes')));
    }

    public function reject(AdminContentApprovalTransitionRequest $request, ContentApprovalQueue $contentApprovalQueue, ContentApprovalQueueService $service): ContentApprovalQueueResource
    {
        return new ContentApprovalQueueResource($service->reject($contentApprovalQueue, $request->user(), $request->validated('admin_notes')));
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
