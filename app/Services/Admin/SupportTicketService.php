<?php

namespace App\Services\Admin;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SupportTicketService
{
    public function __construct(
        private readonly SupportTicket $tickets,
        private readonly SupportTicketReply $replies,
    ) {}

    public function open(User $user, array $data): SupportTicket
    {
        return $this->tickets->newQuery()->create([
            'user_id' => $user->id,
            'subject' => $data['subject'] ?? throw new InvalidArgumentException('Support ticket subject is required.'),
            'category' => $data['category'] ?? throw new InvalidArgumentException('Support ticket category is required.'),
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'description' => $data['description'] ?? throw new InvalidArgumentException('Support ticket description is required.'),
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);
    }

    public function assign(SupportTicket $ticket, User $admin): SupportTicket
    {
        $ticket->forceFill(['assigned_to' => $admin->id])->save();

        return $ticket->refresh();
    }

    public function reply(SupportTicket $ticket, User $sender, string $content, bool $internalNote = false): SupportTicketReply
    {
        if (in_array($ticket->status, ['resolved', 'closed'], true)) {
            throw new InvalidArgumentException('Cannot reply to a resolved or closed support ticket.');
        }

        return DB::transaction(function () use ($ticket, $sender, $content, $internalNote): SupportTicketReply {
            return $this->replies->newQuery()->create([
                'ticket_id' => $ticket->id,
                'sender_id' => $sender->id,
                'content' => $content,
                'is_internal_note' => $internalNote,
            ]);
        });
    }

    public function resolve(SupportTicket $ticket, User $admin, ?string $note = null): SupportTicket
    {
        if ($ticket->status === 'closed') {
            throw new InvalidArgumentException('Cannot resolve a closed support ticket.');
        }

        return DB::transaction(function () use ($ticket, $admin, $note): SupportTicket {
            $ticket->forceFill([
                'status' => 'resolved',
                'assigned_to' => $ticket->assigned_to ?? $admin->id,
                'resolved_at' => now(),
            ])->save();

            if ($note !== null) {
                $this->replies->newQuery()->create([
                    'ticket_id' => $ticket->id,
                    'sender_id' => $admin->id,
                    'content' => $note,
                    'is_internal_note' => true,
                ]);
            }

            return $ticket->refresh();
        });
    }
}
