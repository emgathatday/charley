<?php

namespace App\Services\Admin;

use App\Models\ContentApprovalQueue;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ContentApprovalQueueService
{
    public function __construct(private readonly ContentApprovalQueue $queue) {}

    public function submit(Model $approvable, User $submitter, array $data): ContentApprovalQueue
    {
        return $this->queue->newQuery()->create([
            'approvable_type' => $approvable->getMorphClass(),
            'approvable_id' => $approvable->getKey(),
            'submitted_by' => $submitter->id,
            'submitter_tier' => $data['submitter_tier'] ?? null,
            'content_title' => $data['content_title'] ?? throw new InvalidArgumentException('Content title is required.'),
            'content_type_label' => $data['content_type_label'] ?? throw new InvalidArgumentException('Content type label is required.'),
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending',
            'assigned_to' => $data['assigned_to'] ?? null,
            'submitted_at' => $data['submitted_at'] ?? now(),
        ]);
    }

    public function assign(ContentApprovalQueue $item, User $admin): ContentApprovalQueue
    {
        $item->forceFill(['assigned_to' => $admin->id])->save();

        return $item->refresh();
    }

    public function approve(ContentApprovalQueue $item, User $admin, ?string $notes = null): ContentApprovalQueue
    {
        return $this->transition($item, $admin, 'approved', $notes);
    }

    public function reject(ContentApprovalQueue $item, User $admin, ?string $notes = null): ContentApprovalQueue
    {
        return $this->transition($item, $admin, 'rejected', $notes);
    }

    private function transition(ContentApprovalQueue $item, User $admin, string $status, ?string $notes): ContentApprovalQueue
    {
        if ($item->status !== 'pending') {
            throw new InvalidArgumentException('Only pending content approval items can transition.');
        }

        return DB::transaction(function () use ($item, $admin, $status, $notes): ContentApprovalQueue {
            $item->forceFill([
                'status' => $status,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_notes' => $notes,
            ])->save();

            return $item->refresh();
        });
    }
}
