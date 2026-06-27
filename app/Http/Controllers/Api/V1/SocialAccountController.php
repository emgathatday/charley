<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialAccountLinkRequest;
use App\Http\Resources\SocialAccountResource;
use App\Models\SocialAccount;
use App\Services\SocialAccountService;

class SocialAccountController extends Controller
{
    public function __construct(private readonly SocialAccountService $socialAccountService)
    {
    }

    public function store(SocialAccountLinkRequest $request): SocialAccountResource
    {
        $data = $request->validated();

        return new SocialAccountResource(
            $this->socialAccountService->link($request->user(), $data['provider_name'], $data['provider_id'])
        );
    }

    public function destroy(SocialAccount $socialAccount): SocialAccountResource
    {
        return new SocialAccountResource($this->socialAccountService->unlink($socialAccount));
    }
}
