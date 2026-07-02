<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAssignRequest;
use App\Http\Requests\AdminSupportTicketReplyRequest;
use App\Http\Requests\AdminSupportTicketRequest;
use App\Http\Resources\SupportTicketReplyResource;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Admin\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupportTicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdmin($request);

        return SupportTicketResource::collection(SupportTicket::query()->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(AdminSupportTicketRequest $request, SupportTicketService $service): SupportTicketResource
    {
        return new SupportTicketResource($service->open(User::findOrFail($request->integer('user_id')), $request->validated()));
    }

    public function show(Request $request, SupportTicket $supportTicket): SupportTicketResource
    {
        $this->authorizeAdmin($request);

        return new SupportTicketResource($supportTicket->load('replies'));
    }

    public function assign(AdminAssignRequest $request, SupportTicket $supportTicket, SupportTicketService $service): SupportTicketResource
    {
        return new SupportTicketResource($service->assign($supportTicket, User::findOrFail($request->integer('admin_id'))));
    }

    public function reply(AdminSupportTicketReplyRequest $request, SupportTicket $supportTicket, SupportTicketService $service): SupportTicketReplyResource
    {
        $reply = $service->reply($supportTicket, $request->user(), $request->string('content')->toString(), $request->boolean('is_internal_note'));

        return new SupportTicketReplyResource($reply);
    }

    public function resolve(AdminSupportTicketReplyRequest $request, SupportTicket $supportTicket, SupportTicketService $service): SupportTicketResource
    {
        return new SupportTicketResource($service->resolve($supportTicket, $request->user(), $request->validated('content')));
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
