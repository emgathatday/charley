<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LoginTokenConsumeRequest;
use App\Http\Requests\LoginTokenIssueRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\LoginTokenResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\LoginTokenService;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly LoginTokenService $loginTokenService
    ) {
    }

    public function register(RegisterRequest $request): UserResource
    {
        return new UserResource($this->registrationService->register($request->validated()));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new RuntimeException('Invalid login credentials.');
        }

        $user = $this->registrationService->markLoggedIn($user);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $token = method_exists($request->user(), 'currentAccessToken')
            ? $request->user()?->currentAccessToken()
            : null;

        $token?->delete();

        return response()->json(['data' => ['logged_out' => true]]);
    }

    public function issueLoginToken(LoginTokenIssueRequest $request): JsonResponse
    {
        $data = $request->validated();
        $issued = $this->loginTokenService->issue(
            User::where('email', $data['email'])->firstOrFail(),
            $data['type'],
            $data['expires_in_minutes'] ?? 30
        );

        return response()->json([
            'data' => [
                'plain_token' => $issued['plain_token'],
                'login_token' => new LoginTokenResource($issued['login_token']),
            ],
        ]);
    }

    public function consumeLoginToken(LoginTokenConsumeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $loginToken = $this->loginTokenService->consume($data['token'], $data['type'])->load('user');
        $this->registrationService->markLoggedIn($loginToken->user);

        return response()->json([
            'data' => [
                'login_token' => new LoginTokenResource($loginToken),
                'user' => new UserResource($loginToken->user),
            ],
        ]);
    }
}


