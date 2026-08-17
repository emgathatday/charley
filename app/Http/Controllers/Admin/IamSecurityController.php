<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountPenalty;
use App\Models\User;
use App\Services\Admin\AccountPenaltyService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IamSecurityController extends Controller
{
    public function show(Request $request, ?User $user = null): View
    {
        if ($user) {
            $accountPenalties = $this->accountPenaltiesForUsers(collect([$user->id]));
            $latestVerification = $this->latestVerificationsForUsers(collect([$user->id]))->first();

            return view('iam.user-security-detail', [
                'user' => $user,
                'accountPenalties' => $accountPenalties,
                'latestVerification' => $latestVerification,
            ]);
        }

        $penaltyRows = $this->accountPenaltyRows($request);
        $userIds = $penaltyRows->pluck('user_id')->unique()->values();

        return view('iam.user-security', [
            'penaltyRows' => $penaltyRows,
            'users' => collect(),
            'accountPenalties' => $penaltyRows,
            'latestVerifications' => $this->latestVerificationsForUsers($userIds),
        ]);
    }

    public function update(Request $request, User $user, AccountPenaltyService $penaltyService): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'unverified_member', 'professional', 'partner'])],
            'status' => ['required', Rule::in(['active', 'suspended', 'frozen'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($user, $request, $validated, $penaltyService): void {
            $originalStatus = $user->status;
            $actionType = $this->disciplineActionType($originalStatus, $validated['status']);

            $user->forceFill([
                'role' => $validated['role'],
                'status' => $validated['status'],
                'self_frozen_at' => $validated['status'] === 'frozen' ? ($user->self_frozen_at ?? now()) : null,
            ])->save();

            if ($actionType === null) {
                return;
            }

            $penaltyService->issue($user, $request->user(), $this->penaltyPayload($actionType, $validated['admin_note'] ?? null));

            if ($validated['status'] === 'active') {
                $activePenalties = $penaltyService->activeFor($user);

                if ($activePenalties->isNotEmpty()) {
                    $user->forceFill(['status' => $this->statusForActivePenalties($activePenalties)])->save();
                }
            }
        });

        return back()->with('status', 'Account security controls updated.');
    }

    private function disciplineActionType(string $originalStatus, string $newStatus): ?string
    {
        if ($newStatus === 'suspended' && $originalStatus !== 'suspended') {
            return 'temporary_suspension';
        }

        if ($newStatus === 'frozen' && $originalStatus !== 'frozen') {
            return 'account_freeze';
        }

        if ($newStatus === 'active' && $originalStatus === 'frozen') {
            return 'unfreeze';
        }

        return null;
    }

    private function penaltyPayload(string $actionType, ?string $adminNote): array
    {
        $payload = [
            'action_type' => $actionType,
            'reason' => $adminNote ?: $this->defaultPenaltyReason($actionType),
            'starts_at' => now(),
        ];

        if ($actionType === 'temporary_suspension') {
            $payload['duration_days'] = 14;
            $payload['ends_at'] = now()->addDays(14);
        }

        return $payload;
    }

    private function defaultPenaltyReason(string $actionType): string
    {
        return match ($actionType) {
            'temporary_suspension' => 'Temporary suspension applied from IAM user-security.',
            'account_freeze' => 'Account freeze applied from IAM user-security.',
            'unfreeze' => 'Account freeze lifted from IAM user-security.',
            default => 'Account security action applied from IAM user-security.',
        };
    }

    private function statusForActivePenalties($activePenalties): string
    {
        if ($activePenalties->contains(fn (AccountPenalty $penalty): bool => $penalty->action_type === 'ban')) {
            return 'suspended';
        }

        if ($activePenalties->contains(fn (AccountPenalty $penalty): bool => in_array($penalty->action_type, ['account_freeze', 'self_freeze'], true))) {
            return 'frozen';
        }

        if ($activePenalties->contains(fn (AccountPenalty $penalty): bool => $penalty->action_type === 'temporary_suspension')) {
            return 'suspended';
        }

        return 'active';
    }

    private function accountPenaltyRows(Request $request)
    {
        $query = DB::table('account_penalties')
            ->join('users', 'account_penalties.user_id', '=', 'users.id')
            ->select([
                'account_penalties.id',
                'account_penalties.user_id',
                'account_penalties.action_type',
                'account_penalties.reason',
                'account_penalties.evidence_ref',
                'account_penalties.duration_days',
                'account_penalties.starts_at',
                'account_penalties.ends_at',
                'account_penalties.admin_id',
                'account_penalties.created_at',
                'account_penalties.updated_at',
                'users.username as user_username',
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                'users.email as user_email',
                'users.role as user_role',
                'users.status as user_status',
                'users.is_verified as user_is_verified',
                'users.last_login_at as user_last_login_at',
                'users.login_attempts as user_login_attempts',
                'users.locked_until as user_locked_until',
                'users.mfa_enabled as user_mfa_enabled',
                'users.self_frozen_at as user_self_frozen_at',
                'users.created_at as user_created_at',
            ]);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($query) use ($search): void {
                $query->where('users.first_name', 'ilike', "%{$search}%")
                    ->orWhere('users.last_name', 'ilike', "%{$search}%")
                    ->orWhere('users.username', 'ilike', "%{$search}%")
                    ->orWhere('users.email', 'ilike', "%{$search}%")
                    ->orWhere('account_penalties.reason', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('users.role', $role);
        }

        if ($status = $request->query('status')) {
            match ($status) {
                'warned' => $query->where('account_penalties.action_type', 'warning'),
                'suspended' => $query->where('account_penalties.action_type', 'temporary_suspension'),
                'frozen' => $query->whereIn('account_penalties.action_type', ['account_freeze', 'self_freeze']),
                default => $query->where('users.status', $status),
            };
        }

        if ($violation = $request->query('violation')) {
            $query->where('account_penalties.reason', 'ilike', "%{$violation}%");
        }

        return $query->orderByDesc('account_penalties.created_at')
            ->limit(100)
            ->get()
            ->map(fn ($penalty) => $this->hydratePenaltyDates($penalty));
    }

    private function accountPenaltiesForUsers($userIds)
    {
        if ($userIds->isEmpty()) {
            return collect();
        }

        return DB::table('account_penalties')
            ->whereIn('user_id', $userIds)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($penalty) => $this->hydratePenaltyDates($penalty));
    }

    private function latestVerificationsForUsers($userIds)
    {
        if ($userIds->isEmpty()) {
            return collect();
        }

        return DB::table('verification_requests')
            ->whereIn('user_id', $userIds)
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id')
            ->values();
    }

    private function hydratePenaltyDates(object $penalty): object
    {
        foreach (['starts_at', 'ends_at', 'created_at', 'updated_at', 'user_last_login_at', 'user_locked_until', 'user_self_frozen_at', 'user_created_at'] as $field) {
            if (property_exists($penalty, $field)) {
                $penalty->{$field} = $penalty->{$field} ? Carbon::parse($penalty->{$field}) : null;
            }
        }

        return $penalty;
    }
}