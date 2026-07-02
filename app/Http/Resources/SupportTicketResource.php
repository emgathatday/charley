<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'subject' => $this->subject,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'description' => $this->description,
            'assigned_to' => $this->assigned_to,
            'resolved_at' => $this->resolved_at,
            'replies' => SupportTicketReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
