<?php

namespace Tests\Feature;

use App\Models\EngineerProfile;
use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use Database\Factories\VerificationRequestFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IamVerificationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_middleware_blocks_non_admin_from_verification_queue(): void
    {
        $member = User::factory()->professional()->create();

        $this->actingAs($member)
            ->get(route('admin.dashboard.iam.verification-queue'))
            ->assertForbidden();
    }

    public function test_queue_list_filters_results_preserves_pagination_query_strings_and_shows_counters(): void
    {
        $admin = User::factory()->admin()->create();

        for ($i = 1; $i <= 16; $i++) {
            $user = User::factory()->professional()->create([
                'first_name' => 'Filter',
                'last_name' => sprintf('Applicant %02d', $i),
                'email' => sprintf('filter-applicant-%02d@example.test', $i),
            ]);
            VerificationRequestFactory::new()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'verification_method' => 'work_email',
                'submission_type' => 'initial',
                'document_media_ids' => null,
            ]);
        }

        $hiddenApproved = User::factory()->professional()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Approved',
            'email' => 'hidden-approved@example.test',
        ]);
        VerificationRequestFactory::new()->create([
            'user_id' => $hiddenApproved->id,
            'status' => 'approved',
            'verification_method' => 'work_email',
        ]);
        $hiddenMethod = User::factory()->professional()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Linkedin',
            'email' => 'hidden-linkedin@example.test',
        ]);
        VerificationRequestFactory::new()->create([
            'user_id' => $hiddenMethod->id,
            'status' => 'pending',
            'verification_method' => 'linkedin',
        ]);
        VerificationRequestFactory::new()->create(['status' => 'rejected']);
        VerificationRequestFactory::new()->create(['status' => 'more_info_required']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.verification-queue', [
            'status' => 'pending',
            'method' => 'work_email',
            'search' => 'Filter',
        ]));

        $response->assertOk()
            ->assertViewIs('iam.verification-queue')
            ->assertSee('Profile Verification Queue')
            ->assertSee('Filter Applicant')
            ->assertSee('filter-applicant')
            ->assertDontSee('hidden-approved@example.test')
            ->assertDontSee('hidden-linkedin@example.test')
            ->assertSee('Pending Review')
            ->assertSee('16 applications')
            ->assertSee('Approved')
            ->assertSee('Rejected')
            ->assertSee('Info Requested')
            ->assertSee('status=pending', false)
            ->assertSee('method=work_email', false)
            ->assertSee('search=Filter', false)
            ->assertSee('page=2', false);
    }

    public function test_verification_detail_dispatches_engineer_and_partner_views_with_media_file_documents(): void
    {
        $admin = User::factory()->admin()->create();
        $engineer = User::factory()->professional()->create([
            'first_name' => 'James',
            'last_name' => 'Verifier',
            'email' => 'james.verifier@example.test',
        ]);
        EngineerProfile::create([
            'user_id' => $engineer->id,
            'current_company' => 'Verifier Refinery',
            'position' => 'Senior Process Engineer',
            'industry_specialization' => ['Catalyst Systems'],
            'experience_years' => 14,
            'phone' => '+84 90 000 0000',
        ]);
        $document = $this->createMediaFile($admin, [
            'disk' => 's3',
            'path' => 'private/raw-secret-license.pdf',
            'original_name' => 'Professional License.pdf',
        ]);
        $engineerRequest = VerificationRequestFactory::new()->create([
            'user_id' => $engineer->id,
            'status' => 'pending',
            'verification_method' => 'company_letter',
            'document_media_ids' => [$document->id],
            'notes' => 'Engineer verification note.',
        ]);

        $partner = User::factory()->create([
            'first_name' => 'Partner',
            'last_name' => 'Verifier',
            'email' => 'partner.verifier@example.test',
            'role' => 'partner',
            'status' => 'active',
        ]);
        $tier = SubscriptionTier::findOrFail(DB::table('subscription_tiers')->insertGetId([
            'code' => 'dynamic-verification-tier',
            'name' => 'dynamic-verification-tier',
            'display_name' => 'Dynamic Verification Tier',
            'description' => 'Dynamic tier shown on partner verification detail.',
            'monthly_price' => 500,
            'billing_cycle' => 'monthly',
            'duration_days' => 30,
            'sort_order' => 1,
            'is_public' => true,
            'is_active' => true,
            'ai_monthly_limit' => 100,
            'announcement_frequency' => 'monthly',
            'announcement_limit' => 1,
            'can_host_webinar' => false,
            'can_initiate_message' => false,
            'can_create_poll' => false,
            'can_publish_events' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $subscription = PartnerSubscription::create([
            'user_id' => $partner->id,
            'tier_id' => $tier->id,
            'status' => 'active',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
        PartnerProfile::factory()->approved()->create([
            'user_id' => $partner->id,
            'company_name' => 'Partner Verification Co',
            'contact_email' => 'verify@partner-verification.test',
            'active_partner_subscription_id' => $subscription->id,
            'company_type' => 'Catalyst supplier',
            'country' => 'Singapore',
        ]);
        $partnerDocument = $this->createMediaFile($admin, [
            'disk' => 's3',
            'path' => 'partner/private/raw-registration.pdf',
            'original_name' => 'Registration Certificate.pdf',
        ]);
        $partnerRequest = VerificationRequestFactory::new()->create([
            'user_id' => $partner->id,
            'status' => 'pending',
            'verification_method' => 'company_letter',
            'document_media_ids' => [$partnerDocument->id],
        ]);

        $engineerResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.verification-queue.show', $engineerRequest));
        $partnerResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.verification-queue.show', $partnerRequest));

        $engineerResponse->assertOk()
            ->assertViewIs('iam.verification-detail-engineer')
            ->assertSee('James Verifier')
            ->assertSee('Verifier Refinery')
            ->assertSee('Professional License.pdf')
            ->assertDontSee('private/raw-secret-license.pdf')
            ->assertSee('Review decision')
            ->assertSee(route('admin.dashboard.iam.verification-queue.approve', $engineerRequest), false)
            ->assertSee(route('admin.dashboard.iam.verification-queue.more-info', $engineerRequest), false)
            ->assertSee(route('admin.dashboard.iam.verification-queue.reject', $engineerRequest), false);

        $partnerResponse->assertOk()
            ->assertViewIs('iam.verification-detail-partner')
            ->assertSee('Partner Verification Co')
            ->assertSee('Dynamic Verification Tier')
            ->assertSee('Registration Certificate.pdf')
            ->assertDontSee('partner/private/raw-registration.pdf')
            ->assertSee('Verification Decision')
            ->assertSee(route('admin.dashboard.iam.verification-queue.approve', $partnerRequest), false)
            ->assertSee(route('admin.dashboard.iam.verification-queue.more-info', $partnerRequest), false)
            ->assertSee(route('admin.dashboard.iam.verification-queue.reject', $partnerRequest), false);
    }

    public function test_queue_actions_validate_admin_notes_and_update_review_ownership(): void
    {
        $admin = User::factory()->admin()->create();
        $approvable = VerificationRequestFactory::new()->create([
            'user_id' => User::factory()->professional()->create()->id,
            'status' => 'pending',
        ]);
        $moreInfo = VerificationRequestFactory::new()->create([
            'user_id' => User::factory()->professional()->create()->id,
            'status' => 'pending',
        ]);
        $rejectable = VerificationRequestFactory::new()->create([
            'user_id' => User::factory()->professional()->create()->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.dashboard.iam.verification-queue.more-info', $moreInfo), ['admin_notes' => ''])
            ->assertSessionHasErrors('admin_notes');
        $this->actingAs($admin)
            ->post(route('admin.dashboard.iam.verification-queue.reject', $rejectable), ['admin_notes' => ''])
            ->assertSessionHasErrors('admin_notes');

        $this->actingAs($admin)
            ->post(route('admin.dashboard.iam.verification-queue.approve', $approvable), ['admin_notes' => 'Looks valid.'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Verification request approved.');
        $this->actingAs($admin)
            ->post(route('admin.dashboard.iam.verification-queue.more-info', $moreInfo), ['admin_notes' => 'Please upload a signed letter.'])
            ->assertRedirect()
            ->assertSessionHas('status', 'More information requested.');
        $this->actingAs($admin)
            ->post(route('admin.dashboard.iam.verification-queue.reject', $rejectable), ['admin_notes' => 'Evidence mismatch.'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Verification request rejected.');

        $this->assertDatabaseHas('verification_requests', [
            'id' => $approvable->id,
            'status' => 'approved',
            'admin_notes' => 'Looks valid.',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('verification_requests', [
            'id' => $moreInfo->id,
            'status' => 'more_info_required',
            'admin_notes' => 'Please upload a signed letter.',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('verification_requests', [
            'id' => $rejectable->id,
            'status' => 'rejected',
            'admin_notes' => 'Evidence mismatch.',
            'reviewed_by' => $admin->id,
        ]);

        $this->assertNotNull($approvable->refresh()->reviewed_at);
        $this->assertNotNull($moreInfo->refresh()->reviewed_at);
        $this->assertNotNull($rejectable->refresh()->reviewed_at);
    }

    public function test_partner_verification_approval_preserves_partner_role_and_approves_partner_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = User::factory()->create([
            'role' => 'partner',
            'is_verified' => false,
            'verified_at' => null,
            'verification_expires_at' => null,
            'status' => 'active',
        ]);
        $partnerProfile = PartnerProfile::factory()->create([
            'user_id' => $partner->id,
            'company_name' => 'Partner Approval Guard Co',
            'approval_status' => 'pending',
            'verified_at' => null,
            'partner_tier' => null,
        ]);
        $document = $this->createMediaFile($admin, [
            'path' => 'partner/private/raw-approval-guard.pdf',
            'original_name' => 'Partner Approval Guard.pdf',
        ]);
        $verificationRequest = VerificationRequestFactory::new()->create([
            'user_id' => $partner->id,
            'status' => 'pending',
            'verification_method' => 'company_letter',
            'document_media_ids' => [$document->id],
            'admin_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.dashboard.iam.verification-queue.approve', $verificationRequest), [
                'admin_notes' => 'Partner documentation approved.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Verification request approved.');

        $partner->refresh();
        $verificationRequest->refresh();
        $partnerProfile->refresh();

        $this->assertSame('partner', $partner->role);
        $this->assertTrue($partner->is_verified);
        $this->assertNotNull($partner->verified_at);
        $this->assertNull($partner->verification_expires_at);
        $this->assertSame('approved', $verificationRequest->status);
        $this->assertSame('Partner documentation approved.', $verificationRequest->admin_notes);
        $this->assertSame($admin->id, $verificationRequest->reviewed_by);
        $this->assertNotNull($verificationRequest->reviewed_at);
        $this->assertSame('approved', $partnerProfile->approval_status);
        $this->assertNotNull($partnerProfile->verified_at);
        $this->assertNull($partnerProfile->partner_tier);
        $this->assertSame([$document->id], $verificationRequest->document_media_ids);
        $this->assertStringNotContainsString('partner/private/raw-approval-guard.pdf', json_encode($verificationRequest->document_media_ids));
        $this->assertDatabaseMissing('verification_reminder_schedules', [
            'user_id' => $partner->id,
        ]);
    }

    public function test_unverified_member_verification_approval_promotes_only_member_to_professional(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->unverified()->create([
            'status' => 'active',
        ]);
        $verificationRequest = VerificationRequestFactory::new()->create([
            'user_id' => $member->id,
            'status' => 'pending',
            'verification_method' => 'work_email',
            'admin_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.dashboard.iam.verification-queue.approve', $verificationRequest), [
                'admin_notes' => 'Member identity approved.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Verification request approved.');

        $member->refresh();
        $verificationRequest->refresh();

        $this->assertSame('professional', $member->role);
        $this->assertTrue($member->is_verified);
        $this->assertNotNull($member->verified_at);
        $this->assertNotNull($member->verification_expires_at);
        $this->assertSame('approved', $verificationRequest->status);
        $this->assertSame('Member identity approved.', $verificationRequest->admin_notes);
        $this->assertSame($admin->id, $verificationRequest->reviewed_by);
        $this->assertNotNull($verificationRequest->reviewed_at);
        $this->assertDatabaseHas('verification_reminder_schedules', [
            'user_id' => $member->id,
            'reminder_type' => '30_days_before',
            'status' => 'pending',
        ]);
    }
    private function createMediaFile(User $admin, array $overrides = []): MediaFile
    {
        return MediaFile::create(array_merge([
            'uploader_id' => $admin->id,
            'disk' => 'public',
            'path' => 'verification-documents/document.pdf',
            'original_name' => 'Document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'upload_context' => 'profile_photo',
            'file_category' => 'document',
            'sort_order' => 0,
            'is_watermarked' => false,
            'processing_status' => 'processed',
            'is_orphan' => false,
        ], $overrides));
    }
}

