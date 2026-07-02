<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentApprovalQueueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approvable_type' => $this->approvable_type,
            'approvable_id' => $this->approvable_id,
            'submitted_by' => $this->submitted_by,
            'submitter_tier' => $this->submitter_tier,
            'content_title' => $this->content_title,
            'content_type_label' => $this->content_type_label,
            'priority' => $this->priority,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'admin_notes' => $this->admin_notes,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'submitted_at' => $this->submitted_at,
        ];
    }
}
