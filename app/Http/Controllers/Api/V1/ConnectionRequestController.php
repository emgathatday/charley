<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewConnectionRequestRequest;
use App\Http\Requests\StoreConnectionRequestRequest;
use App\Http\Resources\ConnectionRequestResource;
use App\Models\ConnectionRequest;
use App\Models\User;
use App\Services\PartnerConnectionRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

class ConnectionRequestController extends Controller
{
    public function __construct(private readonly PartnerConnectionRequestService $connectionRequestService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->ensureAdmin($request);

        return ConnectionRequestResource::collection(
            ConnectionRequest::query()
                ->with(['requester', 'targetUser', 'reviewer', 'connection'])
                ->latest()
                ->paginate()
        );
    }

    public function store(StoreConnectionRequestRequest $request): ConnectionRequestResource|JsonResponse
    {
        $data = $request->validated();
        $targetUser = User::query()->findOrFail($data['target_user_id']);

        return $this->respondWithRuntimeErrors(function () use ($request, $targetUser, $data): ConnectionRequestResource {
            return new ConnectionRequestResource(
                $this->connectionRequestService
                    ->create($request->user(), $targetUser, $data['reason'] ?? null)
                    ->load(['requester', 'targetUser', 'reviewer', 'connection'])
            );
        });
    }

    public function show(Request $request, ConnectionRequest $connectionRequest): ConnectionRequestResource
    {
        $this->ensureCanView($request, $connectionRequest);

        return new ConnectionRequestResource(
            $connectionRequest->load(['requester', 'targetUser', 'reviewer', 'connection'])
        );
    }

    public function cancel(Request $request, ConnectionRequest $connectionRequest): ConnectionRequestResource|JsonResponse
    {
        return $this->respondWithRuntimeErrors(function () use ($request, $connectionRequest): ConnectionRequestResource {
            return new ConnectionRequestResource(
                $this->connectionRequestService
                    ->cancel($connectionRequest, $request->user())
                    ->load(['requester', 'targetUser', 'reviewer', 'connection'])
            );
        });
    }

    public function approve(ReviewConnectionRequestRequest $request, ConnectionRequest $connectionRequest): ConnectionRequestResource|JsonResponse
    {
        $data = $request->validated();

        return $this->respondWithRuntimeErrors(function () use ($request, $connectionRequest, $data): ConnectionRequestResource {
            return new ConnectionRequestResource(
                $this->connectionRequestService
                    ->approve($connectionRequest, $request->user(), $data['admin_note'] ?? null)
                    ->load(['requester', 'targetUser', 'reviewer', 'connection'])
            );
        });
    }

    public function reject(ReviewConnectionRequestRequest $request, ConnectionRequest $connectionRequest): ConnectionRequestResource|JsonResponse
    {
        $data = $request->validated();

        return $this->respondWithRuntimeErrors(function () use ($request, $connectionRequest, $data): ConnectionRequestResource {
            return new ConnectionRequestResource(
                $this->connectionRequestService
                    ->reject($connectionRequest, $request->user(), $data['admin_note'] ?? null)
                    ->load(['requester', 'targetUser', 'reviewer', 'connection'])
            );
        });
    }

    private function ensureCanView(Request $request, ConnectionRequest $connectionRequest): void
    {
        if ($request->user()->role === 'admin') {
            return;
        }

        if ((int) $connectionRequest->requester_id === (int) $request->user()->id) {
            return;
        }

        abort(403, 'Unauthorized connection request access.');
    }

    private function ensureAdmin(Request $request): void
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Only admins can manage connection requests.');
        }
    }

    private function respondWithRuntimeErrors(callable $callback): ConnectionRequestResource|JsonResponse
    {
        try {
            return $callback();
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
