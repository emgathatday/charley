<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MfaEnableRequest;
use App\Http\Resources\UserResource;
use App\Services\AccountSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountSecurityController extends Controller
{
    public function __construct(private readonly AccountSecurityService $accountSecurityService)
    {
    }

    public function recordFailedLogin(Request $request): UserResource
    {
        return new UserResource($this->accountSecurityService->recordFailedLogin($request->user()));
    }

    public function enableMfa(MfaEnableRequest $request): JsonResponse
    {
        $recoveryCodes = $this->accountSecurityService->enableMfa($request->user(), $request->validated('secret'));

        return response()->json([
            'data' => [
                'user' => new UserResource($request->user()->refresh()),
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }

    public function freeze(Request $request): UserResource
    {
        return new UserResource($this->accountSecurityService->freeze($request->user()));
    }
}
