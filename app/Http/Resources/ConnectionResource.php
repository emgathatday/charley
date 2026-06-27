<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_id' => $this->requester_id,
            'requester' => new UserResource($this->whenLoaded('requester')),
            'receiver_id' => $this->receiver_id,
            'receiver' => new UserResource($this->whenLoaded('receiver')),
            'status' => $this->status,
            'initiated_context' => $this->initiated_context,
            'declined_at' => $this->declined_at,
            'accepted_at' => $this->accepted_at,
            'blocked_at' => $this->blocked_at,
            'blocked_by' => $this->blocked_by,
            'blocked_by_user' => new UserResource($this->whenLoaded('blockedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
