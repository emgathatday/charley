<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LoginTokenConsumeRequest;
use App\Http\Requests\LoginTokenIssueRequest;
use App\Http\Requests\MfaChallengeRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\LoginTokenResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AccountSecurityService;
use App\Services\LoginTokenService;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly LoginTokenService $loginTokenService,
        private readonly AccountSecurityService $accountSecurityService
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

        if ($user->mfa_enabled) {
            if (($credentials['code'] ?? null) || ($credentials['recovery_code'] ?? null)) {
                if (! $this->accountSecurityService->validMfaCredential($user, $credentials['code'] ?? null, $credentials['recovery_code'] ?? null, consumeRecoveryCode: true)) {
                    throw ValidationException::withMessages(['code' => 'Invalid MFA challenge.']);
                }

                $user = $this->registrationService->markLoggedIn($user);

                return response()->json([
                    'data' => new UserResource($user),
                ]);
            }

            $challengeToken = Str::random(64);
            Cache::put($this->mfaChallengeCacheKey($challengeToken), $user->id, now()->addMinutes(5));

            return response()->json([
                'data' => [
                    'mfa_required' => true,
                    'challenge_token' => $challengeToken,
                ],
            ]);
        }

        $user = $this->registrationService->markLoggedIn($user);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function mfaChallenge(MfaChallengeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $cacheKey = $this->mfaChallengeCacheKey($data['challenge_token']);
        $userId = Cache::get($cacheKey);

        if (! $userId) {
            throw new RuntimeException('Invalid MFA challenge.');
        }

        $user = User::query()->findOrFail($userId);
        if (! $this->accountSecurityService->validMfaCredential($user, $data['code'] ?? null, $data['recovery_code'] ?? null, consumeRecoveryCode: true)) {
            throw ValidationException::withMessages(['code' => 'Invalid MFA challenge.']);
        }

        Cache::forget($cacheKey);
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

    private function mfaChallengeCacheKey(string $challengeToken): string
    {
        return 'auth:mfa-challenge:' . hash('sha256', $challengeToken);
    }
}
