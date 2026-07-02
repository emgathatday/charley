<?php

namespace Tests\Unit;

use App\Models\AdminIntegration;
use App\Models\ContentApprovalQueue;
use App\Models\PlatformSetting;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Services\Admin\AdminIntegrationService;
use App\Services\Admin\ContentApprovalQueueService;
use App\Services\Admin\PlatformSettingService;
use App\Services\Admin\SupportTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AdminOperationsServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_ticket_service_opens_assigns_replies_and_resolves(): void
    {
        $member = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $service = new SupportTicketService(new SupportTicket, new SupportTicketReply);

        $ticket = $service->open($member, [
            'subject' => 'Billing issue',
            'category' => 'subscription_support',
            'description' => 'Invoice is unavailable.',
        ]);

        $this->assertSame('normal', $ticket->priority);

        $assigned = $service->assign($ticket, $admin);
        $this->assertSame($admin->id, $assigned->assigned_to);

        $reply = $service->reply($assigned, $admin, 'Checking now.', true);
        $this->assertTrue($reply->is_internal_note);

        $resolved = $service->resolve($assigned, $admin, null);
        $this->assertSame('resolved', $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
    }

    public function test_support_ticket_service_rejects_invalid_or_closed_operations(): void
    {
        $service = new SupportTicketService(new SupportTicket, new SupportTicketReply);
        $admin = User::factory()->admin()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Support ticket subject is required.');
        $service->open($admin, ['category' => 'other', 'description' => 'Missing subject.']);
    }

    public function test_support_ticket_service_rejects_reply_to_resolved_ticket(): void
    {
        $service = new SupportTicketService(new SupportTicket, new SupportTicketReply);
        $admin = User::factory()->admin()->create();
        $ticket = $this->createSupportTicket($admin, ['status' => 'resolved', 'resolved_at' => now()]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot reply to a resolved or closed support ticket.');
        $service->reply($ticket, $admin, 'Too late.');
    }

    public function test_platform_setting_service_sets_reads_groups_and_validates_keys(): void
    {
        $service = new PlatformSettingService(new PlatformSetting);

        $setting = $service->set('notifications.digest', 'enabled', 'notification', null);

        $this->assertSame('enabled', $service->value($setting->key));
        $this->assertSame('fallback', $service->value('missing.setting', 'fallback'));
        $this->assertSame(['notifications.digest'], $service->group('notification')->pluck('key')->all());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Platform setting key and group are required.');
        $service->set('', 'enabled', 'notification');
    }

    public function test_admin_integration_service_connects_updates_disconnects_and_filters_expired(): void
    {
        $admin = User::factory()->admin()->create();
        $service = new AdminIntegrationService(new AdminIntegration);

        $integration = $service->connect($admin, [
            'provider' => 'gmail',
            'access_token' => 'initial-token',
            'token_expires_at' => now()->subMinute(),
            'config_metadata' => ['mailbox' => 'support'],
        ]);

        $this->assertSame('gmail', $integration->provider);
        $this->assertSame([$integration->id], $service->expired()->pluck('id')->all());

        $updated = $service->connect($admin, [
            'provider' => 'gmail',
            'access_token' => 'updated-token',
            'token_expires_at' => now()->addDay(),
        ]);

        $this->assertSame($integration->id, $updated->id);
        $this->assertSame('updated-token', $updated->access_token);

        $service->disconnect($updated);
        $this->assertDatabaseMissing('admin_integrations', ['id' => $updated->id]);
    }

    public function test_admin_integration_service_validates_provider_and_required_token_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $service = new AdminIntegrationService(new AdminIntegration);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid admin integration provider.');
        $service->connect($admin, ['provider' => 'slack']);
    }

    public function test_content_approval_service_submits_assigns_and_transitions_pending_items(): void
    {
        $submitter = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $service = new ContentApprovalQueueService(new ContentApprovalQueue);
        $approvable = $this->createSupportTicket($submitter);

        $item = $service->submit($approvable, $submitter, [
            'content_title' => 'Plant safety guide',
            'content_type_label' => 'Article',
            'priority' => 'high',
        ]);

        $this->assertSame('pending', $item->status);
        $this->assertSame('high', $item->priority);

        $assigned = $service->assign($item, $admin);
        $this->assertSame($admin->id, $assigned->assigned_to);

        $approved = $service->approve($assigned, $admin, null);
        $this->assertSame('approved', $approved->status);
        $this->assertSame($admin->id, $approved->reviewed_by);
        $this->assertNotNull($approved->reviewed_at);
    }

    public function test_content_approval_service_rejects_missing_titles_and_repeat_transitions(): void
    {
        $submitter = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $service = new ContentApprovalQueueService(new ContentApprovalQueue);
        $approvable = $this->createSupportTicket($submitter);

        try {
            $service->submit($approvable, $submitter, ['content_type_label' => 'Article']);
            $this->fail('Expected missing title exception.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Content title is required.', $exception->getMessage());
        }

        $approved = $this->createContentApproval($submitter, [
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only pending content approval items can transition.');
        $service->reject($approved, $admin, 'Rejected late.');
    }

    private function createSupportTicket(User $user, array $attributes = []): SupportTicket
    {
        return SupportTicket::query()->create(array_merge([
            'user_id' => $user->id,
            'subject' => 'Support request',
            'category' => 'technical_issue',
            'priority' => 'normal',
            'status' => 'open',
            'description' => 'Support request body.',
            'assigned_to' => null,
            'resolved_at' => null,
        ], $attributes));
    }

    private function createContentApproval(User $submitter, array $attributes = []): ContentApprovalQueue
    {
        return ContentApprovalQueue::query()->create(array_merge([
            'approvable_type' => 'post',
            'approvable_id' => 1,
            'submitted_by' => $submitter->id,
            'submitter_tier' => null,
            'content_title' => 'Safety guide',
            'content_type_label' => 'Article',
            'priority' => 'normal',
            'status' => 'pending',
            'assigned_to' => null,
            'admin_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'submitted_at' => now(),
        ], $attributes));
    }
}
