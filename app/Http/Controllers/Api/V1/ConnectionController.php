<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConnectionActionRequest;
use App\Http\Resources\ConnectionResource;
use App\Models\Connection;
use App\Models\User;
use App\Services\ConnectionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConnectionController extends Controller
{
    public function __construct(private readonly ConnectionService $connectionService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return ConnectionResource::collection(
            Connection::query()
                ->with(['requester', 'receiver', 'blockedBy'])
                ->forUser($request->user()->id)
                ->latest()
                ->paginate()
        );
    }

    public function store(ConnectionActionRequest $request): ConnectionResource
    {
        $data = $request->validated();
        $receiver = User::query()->findOrFail($data['receiver_id']);

        return new ConnectionResource(
            $this->connectionService
                ->request($request->user(), $receiver, $data['initiated_context'])
                ->load(['requester', 'receiver', 'blockedBy'])
        );
    }

    public function accept(ConnectionActionRequest $request, Connection $connection): ConnectionResource
    {
        return new ConnectionResource(
            $this->connectionService->accept($connection, $request->user())->load(['requester', 'receiver', 'blockedBy'])
        );
    }

    public function decline(ConnectionActionRequest $request, Connection $connection): ConnectionResource
    {
        return new ConnectionResource(
            $this->connectionService->decline($connection, $request->user())->load(['requester', 'receiver', 'blockedBy'])
        );
    }

    public function block(ConnectionActionRequest $request, Connection $connection): ConnectionResource
    {
        return new ConnectionResource(
            $this->connectionService->block($connection, $request->user())->load(['requester', 'receiver', 'blockedBy'])
        );
    }
}
