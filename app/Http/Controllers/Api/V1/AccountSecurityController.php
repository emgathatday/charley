<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MfaDisableRequest;
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

    public function setupMfa(MfaEnableRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->accountSecurityService->beginMfaSetup($request->user(), $request->validated('secret')),
        ]);
    }

    public function enableMfa(MfaEnableRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['secret'])) {
            return $this->setupMfa($request);
        }

        $result = $this->accountSecurityService->enableMfa($request->user(), $data['secret'], $data['code']);

        return response()->json([
            'data' => [
                'user' => new UserResource($request->user()->refresh()),
                'recovery_codes' => $result['recovery_codes'],
            ],
        ]);
    }

    public function disableMfa(MfaDisableRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $this->accountSecurityService->disableMfa($request->user(), $data['code'] ?? null, $data['recovery_code'] ?? null);

        return response()->json([
            'data' => [
                'user' => new UserResource($user->refresh()),
            ],
        ]);
    }

    public function freeze(Request $request): UserResource
    {
        return new UserResource($this->accountSecurityService->freeze($request->user()));
    }
}
