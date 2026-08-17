<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConnectionActionRequest;
use App\Http\Resources\ConnectionResource;
use App\Models\Connection;
use App\Models\User;
use App\Services\ConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

class ConnectionController extends Controller
{
    public function __construct(private readonly ConnectionService $connectionService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        return ConnectionResource::collection(
            Connection::query()
                ->with(['requester', 'receiver', 'blockedBy'])
                ->where(function ($query) use ($userId): void {
                    $query->where('requester_id', $userId)
                        ->orWhere('receiver_id', $userId);
                })
                ->latest()
                ->paginate()
        );
    }

    public function store(ConnectionActionRequest $request): ConnectionResource|JsonResponse
    {
        $data = $request->validated();
        $receiver = User::query()->findOrFail($data['receiver_id']);

        return $this->respondWithRuntimeErrors(function () use ($request, $receiver, $data): ConnectionResource {
            return new ConnectionResource(
                $this->connectionService
                    ->request($request->user(), $receiver, $data['initiated_context'])
                    ->load(['requester', 'receiver', 'blockedBy'])
            );
        });
    }

    public function show(Request $request, Connection $connection): ConnectionResource
    {
        $this->ensureCanView($request, $connection);

        return new ConnectionResource($connection->load(['requester', 'receiver', 'blockedBy']));
    }

    public function accept(ConnectionActionRequest $request, Connection $connection): ConnectionResource|JsonResponse
    {
        return $this->respondWithRuntimeErrors(function () use ($request, $connection): ConnectionResource {
            return new ConnectionResource(
                $this->connectionService->accept($connection, $request->user())->load(['requester', 'receiver', 'blockedBy'])
            );
        });
    }

    public function decline(ConnectionActionRequest $request, Connection $connection): ConnectionResource|JsonResponse
    {
        return $this->respondWithRuntimeErrors(function () use ($request, $connection): ConnectionResource {
            return new ConnectionResource(
                $this->connectionService->decline($connection, $request->user())->load(['requester', 'receiver', 'blockedBy'])
            );
        });
    }

    public function block(ConnectionActionRequest $request, Connection $connection): ConnectionResource|JsonResponse
    {
        return $this->respondWithRuntimeErrors(function () use ($request, $connection): ConnectionResource {
            return new ConnectionResource(
                $this->connectionService->block($connection, $request->user())->load(['requester', 'receiver', 'blockedBy'])
            );
        });
    }

    public function messagingEligibility(Request $request, Connection $connection): JsonResponse
    {
        $this->ensureCanView($request, $connection);

        return response()->json([
            'connection_id' => $connection->id,
            'can_message' => $this->connectionService->canMessage($connection),
        ]);
    }

    private function ensureCanView(Request $request, Connection $connection): void
    {
        if ($request->user()->role === 'admin') {
            return;
        }

        if (in_array((int) $request->user()->id, [(int) $connection->requester_id, (int) $connection->receiver_id], true)) {
            return;
        }

        abort(403, 'Unauthorized connection access.');
    }

    private function respondWithRuntimeErrors(callable $callback): ConnectionResource|JsonResponse
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
