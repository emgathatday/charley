<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAccountPenaltyRequest;
use App\Http\Resources\AccountPenaltyResource;
use App\Models\AccountPenalty;
use App\Models\User;
use App\Services\Admin\AccountPenaltyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountPenaltyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdmin($request);

        return AccountPenaltyResource::collection(AccountPenalty::query()->latest('starts_at')->paginate($request->integer('per_page', 15)));
    }

    public function store(AdminAccountPenaltyRequest $request, AccountPenaltyService $service): AccountPenaltyResource
    {
        $penalty = $service->issue(User::findOrFail($request->integer('user_id')), $request->user(), $request->validated());

        return new AccountPenaltyResource($penalty);
    }

    public function show(Request $request, AccountPenalty $accountPenalty): AccountPenaltyResource
    {
        $this->authorizeAdmin($request);

        return new AccountPenaltyResource($accountPenalty);
    }

    public function end(Request $request, AccountPenalty $accountPenalty, AccountPenaltyService $service): AccountPenaltyResource
    {
        $this->authorizeAdmin($request);

        return new AccountPenaltyResource($service->end($accountPenalty));
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
