<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_id' => $this->requester_id,
            'requester' => new UserResource($this->whenLoaded('requester')),
            'target_user_id' => $this->target_user_id,
            'target_user' => new UserResource($this->whenLoaded('targetUser')),
            'reason' => $this->reason,
            'status' => $this->status,
            'reviewed_by' => $this->reviewed_by,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'reviewed_at' => $this->reviewed_at,
            'admin_note' => $this->admin_note,
            'connection_id' => $this->connection_id,
            'connection' => new ConnectionResource($this->whenLoaded('connection')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
