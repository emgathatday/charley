<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'sender_id' => $this->sender_id,
            'content' => $this->content,
            'is_internal_note' => $this->is_internal_note,
            'created_at' => $this->created_at,
        ];
    }
}
