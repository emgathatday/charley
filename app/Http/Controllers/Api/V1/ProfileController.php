<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpsertRequest;
use App\Http\Resources\EngineerProfileResource;
use App\Http\Resources\UnverifiedMemberProfileResource;
use App\Models\EngineerProfile;
use App\Models\UnverifiedMemberProfile;
use App\Services\ProfileService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService)
    {
    }

    public function myEngineerProfile(Request $request): EngineerProfileResource
    {
        $profile = EngineerProfile::query()->with('user')->where('user_id', $request->user()->id)->firstOrFail();

        return new EngineerProfileResource($profile);
    }

    public function upsertEngineerProfile(ProfileUpsertRequest $request): EngineerProfileResource
    {
        return new EngineerProfileResource(
            $this->profileService->upsertEngineerProfile($request->user(), $request->validated())->load('user')
        );
    }

    public function showEngineerProfile(Request $request, EngineerProfile $engineerProfile): EngineerProfileResource
    {
        if (! $this->profileService->canViewProfile($request->user(), $engineerProfile)) {
            throw new AuthorizationException('This profile is not visible.');
        }

        return new EngineerProfileResource($engineerProfile->load('user'));
    }

    public function myUnverifiedProfile(Request $request): UnverifiedMemberProfileResource
    {
        $profile = UnverifiedMemberProfile::query()->with('user')->where('user_id', $request->user()->id)->firstOrFail();

        return new UnverifiedMemberProfileResource($profile);
    }

    public function upsertUnverifiedProfile(ProfileUpsertRequest $request): UnverifiedMemberProfileResource
    {
        return new UnverifiedMemberProfileResource(
            $this->profileService->upsertUnverifiedMemberProfile($request->user(), $request->validated())->load('user')
        );
    }

    public function showUnverifiedProfile(Request $request, UnverifiedMemberProfile $unverifiedMemberProfile): UnverifiedMemberProfileResource
    {
        if (! $this->profileService->canViewProfile($request->user(), $unverifiedMemberProfile)) {
            throw new AuthorizationException('This profile is not visible.');
        }

        return new UnverifiedMemberProfileResource($unverifiedMemberProfile->load('user'));
    }
}
