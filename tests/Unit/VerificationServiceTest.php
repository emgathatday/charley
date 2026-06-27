<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\VerificationReminderSchedule;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Database\Factories\VerificationReminderScheduleFactory;
use Database\Factories\VerificationRequestFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class VerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-25 09:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_submit_creates_pending_request_with_nullable_optional_fields(): void
    {
        $user = User::factory()->unverified()->create();

        $request = app(VerificationService::class)->submit($user, [
            'submission_type' => 'initial',
            'verification_method' => 'work_email',
        ]);

        $this->assertSame($user->id, $request->user_id, 'Verification request should belong to the submitting user.');
        $this->assertSame('pending', $request->status, 'New verification request should start pending.');
        $this->assertNull($request->document_media_ids, 'Document media IDs should remain nullable.');
        $this->assertNull($request->notes, 'Notes should remain nullable.');
    }

    public function test_submit_fails_when_user_foreign_key_is_invalid(): void
    {
        $this->expectException(QueryException::class);

        app(VerificationService::class)->submit(User::factory()->make(['id' => 999999]), [
            'submission_type' => 'initial',
            'verification_method' => 'linkedin',
        ]);
    }

    public function test_approve_marks_request_and_user_verified_and_schedules_reminders(): void
    {
        $user = User::factory()->unverified()->create();
        $reviewer = User::factory()->admin()->create();
        $request = VerificationRequestFactory::new()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $approved = app(VerificationService::class)->approve($request, $reviewer, 'Looks good.');
        $freshUser = $user->fresh();

        $this->assertSame('approved', $approved->status, 'Approval should update request status.');
        $this->assertSame('Looks good.', $approved->admin_notes, 'Approval should store admin notes.');
        $this->assertSame($reviewer->id, $approved->reviewed_by, 'Approval should store reviewer.');
        $this->assertTrue($approved->reviewed_at->equalTo(now()), 'Approval should store review time.');
        $this->assertSame('professional', $freshUser->role, 'Approved user should become professional.');
        $this->assertTrue($freshUser->is_verified, 'Approved user should be verified.');
        $this->assertTrue($freshUser->verification_expires_at->equalTo(now()->addYear()), 'Verification expiry should be one year out.');
        $this->assertDatabaseHas('user_activity_feed', [
            'user_id' => $user->id,
            'activity_type' => 'contribution_approved',
            'subject_type' => VerificationRequest::class,
            'subject_id' => $request->id,
            'is_public' => false,
        ]);
        $this->assertSame(4, VerificationReminderSchedule::where('user_id', $user->id)->count(), 'Approval should schedule all renewal reminders.');
    }

    public function test_reject_requires_admin_notes(): void
    {
        $request = VerificationRequestFactory::new()->create(['status' => 'pending']);
        $reviewer = User::factory()->admin()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Admin notes are required when rejecting verification.');

        app(VerificationService::class)->reject($request, $reviewer, '');
    }

    public function test_reject_updates_request_state_with_notes(): void
    {
        $request = VerificationRequestFactory::new()->create(['status' => 'pending']);
        $reviewer = User::factory()->admin()->create();

        $rejected = app(VerificationService::class)->reject($request, $reviewer, 'Need stronger evidence.');

        $this->assertSame('rejected', $rejected->status, 'Reject should update request status.');
        $this->assertSame('Need stronger evidence.', $rejected->admin_notes, 'Reject should store admin notes.');
        $this->assertSame($reviewer->id, $rejected->reviewed_by, 'Reject should store reviewer.');
        $this->assertTrue($rejected->reviewed_at->equalTo(now()), 'Reject should store review time.');
    }

    public function test_schedule_renewal_reminders_requires_expiry_date(): void
    {
        $user = User::factory()->create(['verification_expires_at' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User does not have a verification expiry date.');

        app(VerificationService::class)->scheduleRenewalReminders($user);
    }

    public function test_schedule_renewal_reminders_creates_expected_dates_and_resets_sent_state(): void
    {
        $user = User::factory()->professional()->create([
            'verification_expires_at' => Carbon::parse('2026-12-31 12:00:00'),
        ]);

        VerificationReminderScheduleFactory::new()->sent()->create([
            'user_id' => $user->id,
            'reminder_type' => '30_days_before',
            'scheduled_at' => Carbon::parse('2026-01-01 12:00:00'),
        ]);

        app(VerificationService::class)->scheduleRenewalReminders($user);

        $expected = [
            '30_days_before' => '2026-12-01 12:00:00',
            '7_days_before' => '2026-12-24 12:00:00',
            'expiry_day' => '2026-12-31 12:00:00',
            'expired_notice' => '2027-01-01 12:00:00',
        ];

        foreach ($expected as $type => $scheduledAt) {
            $schedule = VerificationReminderSchedule::where('user_id', $user->id)
                ->where('reminder_type', $type)
                ->firstOrFail();

            $this->assertSame($scheduledAt, $schedule->scheduled_at->toDateTimeString(), "{$type} should be scheduled at the expected time.");
            $this->assertNull($schedule->sent_at, "{$type} sent marker should be reset.");
            $this->assertSame('pending', $schedule->status, "{$type} should be pending.");
        }
    }
}
