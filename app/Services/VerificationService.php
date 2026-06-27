<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityFeed;
use App\Models\VerificationReminderSchedule;
use App\Models\VerificationRequest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VerificationService
{
    public function submit(User $user, array $data): VerificationRequest
    {
        return DB::transaction(function () use ($user, $data): VerificationRequest {
            return VerificationRequest::create([
                'user_id' => $user->id,
                'submission_type' => $data['submission_type'],
                'verification_method' => $data['verification_method'],
                'document_media_ids' => $data['document_media_ids'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'admin_notes' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        });
    }

    public function approve(VerificationRequest $request, User $reviewer, ?string $adminNotes = null): VerificationRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $adminNotes): VerificationRequest {
            $request->forceFill([
                'status' => 'approved',
                'admin_notes' => $adminNotes,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $request->user->forceFill([
                'role' => 'professional',
                'is_verified' => true,
                'verified_at' => now(),
                'verification_expires_at' => now()->addYear(),
            ])->save();

            UserActivityFeed::create([
                'user_id' => $request->user_id,
                'activity_type' => 'contribution_approved',
                'subject_type' => VerificationRequest::class,
                'subject_id' => $request->id,
                'is_public' => false,
                'created_at' => now(),
            ]);

            $this->scheduleRenewalReminders($request->user);

            return $request;
        });
    }

    public function reject(VerificationRequest $request, User $reviewer, string $adminNotes): VerificationRequest
    {
        if ($adminNotes === '') {
            throw new RuntimeException('Admin notes are required when rejecting verification.');
        }

        $request->forceFill([
            'status' => 'rejected',
            'admin_notes' => $adminNotes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        return $request;
    }

    public function scheduleRenewalReminders(User $user): void
    {
        if (! $user->verification_expires_at) {
            throw new RuntimeException('User does not have a verification expiry date.');
        }

        foreach ([
            '30_days_before' => $user->verification_expires_at->copy()->subDays(30),
            '7_days_before' => $user->verification_expires_at->copy()->subDays(7),
            'expiry_day' => $user->verification_expires_at->copy(),
            'expired_notice' => $user->verification_expires_at->copy()->addDay(),
        ] as $type => $scheduledAt) {
            VerificationReminderSchedule::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'reminder_type' => $type,
                ],
                [
                    'scheduled_at' => $scheduledAt,
                    'sent_at' => null,
                    'status' => 'pending',
                ]
            );
        }
    }
}
