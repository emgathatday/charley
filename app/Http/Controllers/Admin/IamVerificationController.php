<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class IamVerificationController extends Controller
{
    public function __construct(private readonly VerificationService $verificationService)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', ''),
            'method' => (string) $request->input('method', ''),
        ];

        $verificationRequests = VerificationRequest::query()
            ->with([
                'user:id,username,first_name,last_name,email,role,is_verified,created_at',
                'reviewer:id,username,first_name,last_name,email,role',
            ])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['method'] !== '', fn ($query) => $query->where('verification_method', $filters['method']))
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = strtolower($filters['search']);

                $query->whereHas('user', function ($query) use ($search): void {
                    $query->whereRaw('lower(username) like ?', ["%{$search}%"])
                        ->orWhereRaw('lower(first_name) like ?', ["%{$search}%"])
                        ->orWhereRaw('lower(last_name) like ?', ["%{$search}%"])
                        ->orWhereRaw("lower(coalesce(first_name, '') || ' ' || coalesce(last_name, '')) like ?", ["%{$search}%"])
                        ->orWhereRaw('lower(email) like ?', ["%{$search}%"]);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $verificationRequests->getCollection()->each(fn (VerificationRequest $verificationRequest) => $this->decorateVerificationRequest($verificationRequest));

        return view('iam.verification-queue', [
            'verificationRequests' => $verificationRequests,
            'filters' => $filters,
            'queueStats' => $this->queueStats(),
            'methodOptions' => ['work_email', 'linkedin', 'company_letter', 'university_letter', 'justification_letter'],
            'statusOptions' => ['pending', 'approved', 'rejected', 'more_info_required'],
        ]);
    }

    public function show(VerificationRequest $verificationRequest): View
    {
        $verificationRequest->load([
            'user:id,username,first_name,last_name,email,role,is_verified,verified_at,verification_expires_at,status,created_at',
            'reviewer:id,username,first_name,last_name,email,role',
        ]);
        $this->decorateVerificationRequest($verificationRequest);

        $user = $verificationRequest->user;
        $role = (string) ($user?->role ?? '');

        if ($role === 'partner') {
            $partnerProfile = $this->partnerProfile((int) $user->id);

            return view('iam.verification-detail-partner', [
                'verificationRequest' => $verificationRequest,
                'user' => $user,
                'profile' => $partnerProfile,
                'documents' => $verificationRequest->document_media,
                'subscription' => $this->partnerSubscription((int) $user->id, (int) ($partnerProfile->active_partner_subscription_id ?? 0)),
                'subscriptionTiers' => $this->subscriptionTiers(),
            ]);
        }

        return view('iam.verification-detail-engineer', [
            'verificationRequest' => $verificationRequest,
            'user' => $user,
            'profile' => $this->engineerProfile((int) ($user?->id ?? 0)),
            'documents' => $verificationRequest->document_media,
        ]);
    }
    public function approve(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->verificationService->approve($verificationRequest, $request->user(), $validated['admin_notes'] ?? null);

        return back()->with('status', 'Verification request approved.');
    }

    public function reject(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
        ]);

        $this->verificationService->reject($verificationRequest, $request->user(), $validated['admin_notes']);

        return back()->with('status', 'Verification request rejected.');
    }

    public function requestMoreInfo(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
        ]);

        $verificationRequest->forceFill([
            'status' => 'more_info_required',
            'admin_notes' => $validated['admin_notes'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with('status', 'More information requested.');
    }

    private function queueStats(): array
    {
        $counts = VerificationRequest::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'all' => (int) $counts->sum(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'more_info_required' => (int) ($counts['more_info_required'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    private function decorateVerificationRequest(VerificationRequest $verificationRequest): void
    {
        $verificationRequest->document_media = $this->documentMedia($verificationRequest->document_media_ids);
        $verificationRequest->applicant_name = $this->displayName($verificationRequest->user);
        $verificationRequest->reviewer_name = $verificationRequest->reviewer ? $this->displayName($verificationRequest->reviewer) : null;
        $verificationRequest->applicant_type_label = $this->applicantTypeLabel((string) ($verificationRequest->user?->role ?? ''));
        $verificationRequest->applicant_type_class = $this->applicantTypeClass((string) ($verificationRequest->user?->role ?? ''));
        $verificationRequest->expertise_label = $this->expertiseLabel((string) ($verificationRequest->user?->role ?? ''));
        $verificationRequest->status_label = $this->statusLabel((string) $verificationRequest->status);
        $verificationRequest->status_class = $this->statusClass((string) $verificationRequest->status);
        $verificationRequest->method_label = $this->humanLabel((string) $verificationRequest->verification_method);
        $verificationRequest->submission_type_label = $this->humanLabel((string) $verificationRequest->submission_type);
        $verificationRequest->sla = $this->slaMeta($verificationRequest);
    }

    private function engineerProfile(int $userId): ?object
    {
        if ($userId <= 0 || ! Schema::hasTable('engineer_profiles')) {
            return null;
        }

        return DB::table('engineer_profiles')->where('user_id', $userId)->first();
    }

    private function partnerProfile(int $userId): ?object
    {
        if ($userId <= 0 || ! Schema::hasTable('partner_profiles')) {
            return null;
        }

        return DB::table('partner_profiles')->where('user_id', $userId)->first();
    }

    private function partnerSubscription(int $userId, int $preferredSubscriptionId = 0): ?object
    {
        if ($userId <= 0 || ! Schema::hasTable('partner_subscriptions')) {
            return null;
        }

        $query = DB::table('partner_subscriptions')
            ->leftJoin('subscription_tiers', 'subscription_tiers.id', '=', 'partner_subscriptions.tier_id')
            ->where('partner_subscriptions.user_id', $userId)
            ->select([
                'partner_subscriptions.*',
                'subscription_tiers.code as tier_code',
                'subscription_tiers.display_name as tier_display_name',
                'subscription_tiers.name as tier_name',
                'subscription_tiers.description as tier_description',
            ]);

        if ($preferredSubscriptionId > 0) {
            $query->orderByRaw('case when partner_subscriptions.id = ? then 0 else 1 end', [$preferredSubscriptionId]);
        }

        return $query->orderByRaw("case when partner_subscriptions.status = 'active' then 0 else 1 end")
            ->orderByDesc('partner_subscriptions.starts_at')
            ->orderByDesc('partner_subscriptions.id')
            ->first();
    }

    private function subscriptionTiers(): array
    {
        if (! Schema::hasTable('subscription_tiers')) {
            return [];
        }

        return DB::table('subscription_tiers')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get(['id', 'code', 'display_name', 'name', 'description'])
            ->all();
    }
    private function documentMedia(mixed $documentMediaIds): array
    {
        $ids = collect($this->decodeDocumentMediaIds($documentMediaIds))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        if (! Schema::hasTable('media_files')) {
            return [];
        }

        return DB::table('media_files')
            ->whereIn('id', $ids->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'disk', 'path', 'original_name', 'mime_type', 'size'])
            ->map(fn ($media) => [
                'id' => (int) $media->id,
                'name' => $media->original_name ?: sprintf('Document #%d', $media->id),
                'mime_type' => $media->mime_type,
                'size' => $media->size ? (int) $media->size : null,
                'url' => $this->mediaUrl((string) ($media->disk ?: 'public'), (string) $media->path),
            ])
            ->all();
    }

    private function decodeDocumentMediaIds(mixed $documentMediaIds): array
    {
        if (is_array($documentMediaIds)) {
            return $documentMediaIds;
        }

        if (! is_string($documentMediaIds) || trim($documentMediaIds) === '') {
            return [];
        }

        $decoded = json_decode($documentMediaIds, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function mediaUrl(string $disk, string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        try {
            return Storage::disk($disk ?: 'public')->url($path);
        } catch (\Throwable) {
            return null;
        }
    }

    private function displayName(?object $user): string
    {
        if (! $user) {
            return 'Unknown applicant';
        }

        $name = trim(implode(' ', array_filter([$user->first_name ?? null, $user->last_name ?? null])));

        return $name !== '' ? $name : (($user->username ?? null) ?: ($user->email ?? 'Unknown applicant'));
    }

    private function applicantTypeLabel(string $role): string
    {
        return match ($role) {
            'partner' => 'Partner',
            'professional' => 'Professional',
            'unverified_member' => 'Registered Member',
            default => $this->humanLabel($role ?: 'User'),
        };
    }

    private function applicantTypeClass(string $role): string
    {
        return match ($role) {
            'partner' => 'diamond',
            'professional' => 'professional',
            'unverified_member' => 'gold',
            default => 'professional',
        };
    }

    private function expertiseLabel(string $role): string
    {
        return match ($role) {
            'partner' => 'Partner organization',
            'professional' => 'Industry Professional',
            'unverified_member' => 'Pending verification',
            default => 'Applicant',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'more_info_required' => 'Info requested',
            default => $this->humanLabel($status),
        };
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            'more_info_required' => 'info-req',
            default => 'pending',
        };
    }

    private function slaMeta(VerificationRequest $verificationRequest): array
    {
        if ($verificationRequest->status === 'rejected') {
            return ['class' => 'ok', 'label' => 'Closed', 'sub' => $verificationRequest->admin_notes ?: 'Rejected'];
        }

        if ($verificationRequest->status === 'approved') {
            return ['class' => 'ok', 'label' => 'Approved', 'sub' => $verificationRequest->reviewed_at?->format('M j, Y') ?? 'Reviewed'];
        }

        $deadline = $verificationRequest->created_at?->copy()->addHours(48);
        if (! $deadline) {
            return ['class' => 'ok', 'label' => 'On track', 'sub' => '-'];
        }

        if ($deadline->isPast()) {
            return ['class' => 'overdue', 'label' => 'Overdue', 'sub' => $deadline->diffForHumans(null, true).' past SLA'];
        }

        if (now()->diffInHours($deadline) <= 8) {
            return ['class' => 'warn', 'label' => 'Due soon', 'sub' => $deadline->diffForHumans(null, true).' remaining'];
        }

        return ['class' => 'ok', 'label' => 'On track', 'sub' => $deadline->diffForHumans(null, true).' remaining'];
    }

    private function humanLabel(string $value): string
    {
        return str_replace(' ', ' ', ucwords(str_replace('_', ' ', $value)));
    }
}



