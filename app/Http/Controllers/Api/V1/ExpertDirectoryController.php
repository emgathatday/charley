<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpertDirectorySearchRequest;
use App\Http\Resources\SearchIndexEntryResource;
use App\Models\Connection;
use App\Models\ConnectionRequest;
use App\Models\User;
use App\Services\PartnerConnectionRequestService;
use App\Services\ProfileSearchIndexService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpertDirectoryController extends Controller
{
    public function __construct(
        private readonly ProfileSearchIndexService $searchIndexService,
        private readonly PartnerConnectionRequestService $connectionRequestService,
    ) {}

    public function index(ExpertDirectorySearchRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $query = $this->searchIndexService->expertDirectoryQuery($data['q'] ?? null, $data);

        if (array_key_exists('is_discoverable', $data)) {
            $query->where('is_discoverable', $data['is_discoverable']);
        }

        $entries = $query->latest('last_indexed_at')->paginate($data['per_page'] ?? 15);
        $entries->getCollection()->load(['indexable.user', 'indexable.plantTypes']);

        $connectionActions = $entries->getCollection()
            ->mapWithKeys(fn ($entry): array => [
                $entry->id => $this->connectionActionState($request->user(), $entry->indexable?->user),
            ])
            ->all();

        return SearchIndexEntryResource::collection($entries)
            ->additional(['meta' => ['connection_actions' => $connectionActions]]);
    }

    private function connectionActionState(?User $actor, ?User $target): array
    {
        if (! $actor || ! $target || (int) $actor->id === (int) $target->id) {
            return ['state' => 'unavailable', 'can_message' => false, 'action' => null];
        }

        $connection = $this->existingConnection($actor, $target);
        if ($connection) {
            return [
                'state' => $connection->status,
                'can_message' => $connection->status === 'accepted',
                'connection_id' => $connection->id,
                'action' => $connection->status === 'accepted' ? 'message' : null,
            ];
        }

        $connectionRequest = $this->latestConnectionRequest($actor, $target);
        if ($connectionRequest && in_array($connectionRequest->status, ['pending', 'approved'], true)) {
            return [
                'state' => 'admin_review_'.$connectionRequest->status,
                'can_message' => false,
                'connection_request_id' => $connectionRequest->id,
                'action' => null,
            ];
        }

        if ($actor->role === 'partner' && $this->isEngineer($target)) {
            return $this->connectionRequestService->hasActivePartnerSubscription($actor)
                ? ['state' => 'available_admin_mediated', 'can_message' => false, 'action' => 'connection_request']
                : ['state' => 'subscription_required', 'can_message' => false, 'action' => null];
        }

        if ($this->directContext($actor, $target) !== null) {
            return ['state' => 'available_direct', 'can_message' => false, 'action' => 'direct_connection'];
        }

        return ['state' => 'unavailable', 'can_message' => false, 'action' => null];
    }

    private function existingConnection(User $actor, User $target): ?Connection
    {
        return Connection::query()
            ->where(function ($query) use ($actor, $target): void {
                $query->where('requester_id', $actor->id)->where('receiver_id', $target->id);
            })
            ->orWhere(function ($query) use ($actor, $target): void {
                $query->where('requester_id', $target->id)->where('receiver_id', $actor->id);
            })
            ->latest()
            ->first();
    }

    private function latestConnectionRequest(User $actor, User $target): ?ConnectionRequest
    {
        return ConnectionRequest::query()
            ->where('requester_id', $actor->id)
            ->where('target_user_id', $target->id)
            ->latest()
            ->first();
    }

    private function directContext(User $actor, User $target): ?string
    {
        if ($this->isEngineer($actor) && $this->isEngineer($target)) {
            return 'engineer_to_engineer';
        }

        if ($this->isEngineer($actor) && $target->role === 'partner') {
            return 'engineer_to_partner';
        }

        return null;
    }

    private function isEngineer(User $user): bool
    {
        return in_array($user->role, ['professional', 'unverified_member'], true);
    }
}
