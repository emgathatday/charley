<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerificationReviewRequest;
use App\Http\Requests\VerificationSubmitRequest;
use App\Http\Resources\VerificationRequestResource;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VerificationRequestController extends Controller
{
    public function __construct(private readonly VerificationService $verificationService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return VerificationRequestResource::collection(
            VerificationRequest::query()->latest()->paginate()
        );
    }

    public function store(VerificationSubmitRequest $request): VerificationRequestResource
    {
        return new VerificationRequestResource(
            $this->verificationService->submit($request->user(), $request->validated())
        );
    }

    public function approve(VerificationReviewRequest $request, VerificationRequest $verificationRequest): VerificationRequestResource
    {
        return new VerificationRequestResource(
            $this->verificationService->approve($verificationRequest, $request->user(), $request->validated('admin_notes'))
        );
    }

    public function reject(VerificationReviewRequest $request, VerificationRequest $verificationRequest): VerificationRequestResource
    {
        return new VerificationRequestResource(
            $this->verificationService->reject($verificationRequest, $request->user(), $request->validated('admin_notes') ?? '')
        );
    }
}
