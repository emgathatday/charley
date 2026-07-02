<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountPenalty;
use App\Models\AdminIntegration;
use App\Models\ContentApprovalQueue;
use App\Models\PlatformSetting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Admin\AccountPenaltyService;
use App\Services\Admin\AdminIntegrationService;
use App\Services\Admin\ContentApprovalQueueService;
use App\Services\Admin\PlatformSettingService;
use App\Services\Admin\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminOperationsController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.admin-operations.index', [
            'availableTables' => $this->availableTables(),
            'filters' => $request->only(['ticket_status', 'approval_status', 'setting_group']),
            'stats' => [
                'open_tickets' => $this->hasTable('support_tickets') ? SupportTicket::where('status', 'open')->count() : 0,
                'active_penalties' => $this->hasTable('account_penalties') ? AccountPenalty::query()->active()->count() : 0,
                'pending_approvals' => $this->hasTable('content_approval_queue') ? ContentApprovalQueue::where('status', 'pending')->count() : 0,
                'integrations' => $this->hasTable('admin_integrations') ? AdminIntegration::count() : 0,
            ],
            'supportTickets' => $this->hasTable('support_tickets') ? SupportTicket::query()->with(['user', 'assignee', 'replies.sender'])->when($request->filled('ticket_status'), fn ($query) => $query->where('status', $request->input('ticket_status')))->latest()->limit(10)->get() : collect(),
            'accountPenalties' => $this->hasTable('account_penalties') ? AccountPenalty::query()->with(['user', 'admin'])->latest('starts_at')->limit(10)->get() : collect(),
            'contentApprovals' => $this->hasTable('content_approval_queue') ? ContentApprovalQueue::query()->with(['submitter', 'assignee', 'reviewer'])->when($request->filled('approval_status'), fn ($query) => $query->where('status', $request->input('approval_status')))->latest('submitted_at')->limit(10)->get() : collect(),
            'platformSettings' => $this->hasTable('platform_settings') ? PlatformSetting::query()->when($request->filled('setting_group'), fn ($query) => $query->where('group', $request->input('setting_group')))->orderBy('group')->orderBy('key')->limit(20)->get() : collect(),
            'adminIntegrations' => $this->hasTable('admin_integrations') ? AdminIntegration::query()->with('user')->latest()->limit(10)->get() : collect(),
            'adminUsers' => User::query()->where('role', 'admin')->orderBy('email')->get(),
        ]);
    }

    public function createTicket(): View
    {
        return view('admin.admin-operations.support-tickets.create', ['users' => User::query()->orderBy('email')->get()]);
    }

    public function storeTicket(Request $request, SupportTicketService $service): RedirectResponse
    {
        $this->abortIfMissingTable('support_tickets');
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['subscription_support', 'technical_issue', 'content_approval', 'account_issue', 'other'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'description' => ['required', 'string'],
        ]);
        $service->open(User::findOrFail($validated['user_id']), $validated);
        return redirect()->route('admin.dashboard.admin-operations.index')->with('status', 'Support ticket created.');
    }

    public function createPenalty(): View
    {
        return view('admin.admin-operations.account-penalties.create', ['users' => User::query()->orderBy('email')->get()]);
    }

    public function storePenalty(Request $request, AccountPenaltyService $service): RedirectResponse
    {
        $this->abortIfMissingTable('account_penalties');
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'action_type' => ['required', Rule::in(['warning', 'temporary_suspension', 'account_freeze', 'unfreeze', 'ban', 'self_freeze', 'self_unfreeze'])],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'reason' => ['required', 'string'],
        ]);
        $service->issue(User::findOrFail($validated['user_id']), $request->user(), $validated);
        return redirect()->route('admin.dashboard.admin-operations.index')->with('status', 'Account penalty created.');
    }

    public function editSetting(?PlatformSetting $platformSetting = null): View
    {
        return view('admin.admin-operations.platform-settings.edit', ['platformSetting' => $platformSetting]);
    }

    public function storeSetting(Request $request, PlatformSettingService $service): RedirectResponse
    {
        $this->abortIfMissingTable('platform_settings');
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string'],
            'group' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $service->set($validated['key'], $validated['value'], $validated['group'], $validated['description'] ?? null);
        return redirect()->route('admin.dashboard.admin-operations.index')->with('status', 'Platform setting saved.');
    }

    public function createIntegration(): View
    {
        return view('admin.admin-operations.admin-integrations.create', ['adminUsers' => User::query()->where('role', 'admin')->orderBy('email')->get()]);
    }

    public function storeIntegration(Request $request, AdminIntegrationService $service): RedirectResponse
    {
        $this->abortIfMissingTable('admin_integrations');
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'provider' => ['required', Rule::in(['outlook', 'gmail'])],
            'access_token' => ['required', 'string'],
            'refresh_token' => ['nullable', 'string'],
            'token_expires_at' => ['required', 'date'],
        ]);
        $service->connect(User::findOrFail($validated['user_id']), $validated);
        return redirect()->route('admin.dashboard.admin-operations.index')->with('status', 'Admin integration saved.');
    }

    public function replyTicket(Request $request, SupportTicket $supportTicket, SupportTicketService $service): RedirectResponse
    {
        $validated = $request->validate(['content' => ['required', 'string'], 'is_internal_note' => ['nullable', 'boolean']]);
        $service->reply($supportTicket, $request->user(), $validated['content'], $request->boolean('is_internal_note'));
        return redirect()->route('admin.dashboard.admin-operations.index')->with('status', 'Ticket reply added.');
    }

    public function resolveTicket(Request $request, SupportTicket $supportTicket, SupportTicketService $service): RedirectResponse
    {
        $service->resolve($supportTicket, $request->user(), $request->input('content'));
        return redirect()->route('admin.dashboard.admin-operations.index')->with('status', 'Ticket resolved.');
    }

    public function assignContent(Request $request, ContentApprovalQueue $contentApprovalQueue, ContentApprovalQueueService $service): RedirectResponse
    {
        $validated = $request->validate(['admin_id' => ['required', 'integer', 'exists:users,id']]);
        $service->assign($contentApprovalQueue, User::findOrFail($validated['admin_id']));
        return redirect()->route('admin.dashboard.admin-operations.index')->with('status', 'Approval item assigned.');
    }

    public function approveContent(Request $request, ContentApprovalQueue $contentApprovalQueue, ContentApprovalQueueService $service): RedirectResponse
    {
        $service->approve($contentApprovalQueue, $request->user(), $request->input('admin_notes'));
        return redirect()->route('admin.dashboard.admin-operations.index')->with('status', 'Content approved.');
    }

    public function rejectContent(Request $request, ContentApprovalQueue $contentApprovalQueue, ContentApprovalQueueService $service): RedirectResponse
    {
        $service->reject($contentApprovalQueue, $request->user(), $request->input('admin_notes'));
        return redirect()->route('admin.dashboard.admin-operations.index')->with('status', 'Content rejected.');
    }

    private function availableTables(): array
    {
        return [
            'support_tickets' => $this->hasTable('support_tickets'),
            'account_penalties' => $this->hasTable('account_penalties'),
            'content_approval_queue' => $this->hasTable('content_approval_queue'),
            'platform_settings' => $this->hasTable('platform_settings'),
            'admin_integrations' => $this->hasTable('admin_integrations'),
        ];
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function abortIfMissingTable(string $table): void
    {
        abort_unless($this->hasTable($table), 503, "Database table [{$table}] is not available.");
    }
}
