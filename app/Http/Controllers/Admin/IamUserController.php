<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlantType;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class IamUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'keyword' => trim((string) $request->input('keyword', $request->input('search', ''))),
            'plant_type_id' => (string) $request->input('plant_type_id', ''),
            'tab' => (string) $request->input('tab', 'active'),
            'member_view' => (string) $request->input('member_view', ''),
            'navigation_role' => (string) $request->input('role', ''),
        ];

        if (! in_array($filters['tab'], ['active', 'pending', 'frozen', 'suspended'], true)) {
            $filters['tab'] = 'active';
        }

        $memberView = $this->memberView($filters['member_view'], $filters['navigation_role']);
        $filters['member_view'] = $memberView;
        unset($filters['navigation_role']);
        $plantTypeOptions = $this->plantTypeOptions();

        if ($filters['plant_type_id'] !== '' && ! in_array((int) $filters['plant_type_id'], array_map('intval', array_keys($plantTypeOptions)), true)) {
            $filters['plant_type_id'] = '';
        }

        $users = $this->baseUserQuery($memberView)
            ->withCount([
                'verificationRequests',
                'verificationRequests as pending_verification_requests_count' => fn (Builder $query) => $query->where('status', 'pending'),
            ])
            ->when($filters['keyword'] !== '', function (Builder $query) use ($filters): void {
                $keyword = strtolower($filters['keyword']);

                $query->where(function (Builder $query) use ($keyword): void {
                    $query->whereRaw('lower(users.username) like ?', ["%{$keyword}%"])
                        ->orWhereRaw('lower(users.first_name) like ?', ["%{$keyword}%"])
                        ->orWhereRaw('lower(users.last_name) like ?', ["%{$keyword}%"])
                        ->orWhereRaw('lower(users.email) like ?', ["%{$keyword}%"]);
                });
            });

        $this->applyPlantTypeFilter($users, $memberView, $filters['plant_type_id']);
        $this->applyTabScope($users, $memberView, $filters['tab']);

        $users = $users
            ->latest('users.created_at')
            ->paginate(10)
            ->withQueryString();

        $users->getCollection()->each(function (User $user): void {
            $user->display_id = (string) $user->id;
            $user->display_name = $this->displayName($user);
            $user->plant_type_label = $this->plantTypeLabel($user);
            $user->experience_label = $this->experienceLabel($user);
            $user->security_label = $this->securityLabel($user);
            $user->status_label = $this->statusLabel($user);
            $user->status_badge = $this->statusBadge($user);
            $user->latest_verification_status = $user->pending_verification_requests_count > 0
                ? 'Pending approval'
                : ($user->is_verified ? 'Verified' : 'Not verified');

            if ($user->role === 'partner' && ($user->partner_approval_status ?? null)) {
                $user->latest_verification_status = str_replace('_', ' ', ucfirst((string) $user->partner_approval_status));
            }
        });

        $stats = $this->statsFor($memberView);
        $tabs = [
            'active' => [
                'label' => 'Active Members',
                'count' => $stats['active_members'],
            ],
            'pending' => [
                'label' => 'Pending Approvals',
                'count' => $stats['pending_approvals'],
            ],
            'frozen' => [
                'label' => 'Frozen',
                'count' => $stats['frozen_users'],
            ],
            'suspended' => [
                'label' => 'Suspended',
                'count' => $stats['suspended_users'],
            ],
        ];

        return view('iam.users', [
            'users' => $users,
            'stats' => $stats,
            'tabs' => $tabs,
            'filters' => $filters,
            'memberView' => $memberView,
            'pageTitle' => $this->pageTitle($memberView),
            'plantTypeOptions' => $plantTypeOptions,
        ]);
    }

    public function show(User $user): View
    {
        $profile = $this->profileFor($user);
        $verificationSummary = $this->verificationSummary($user);
        $detail = [
            'id' => (string) $user->id,
            'name' => $this->displayName($user),
            'email' => $user->email,
            'username' => $user->username ?: 'Not set',
            'role' => str_replace('_', ' ', $user->role),
            'status' => $this->statusLabel($user),
            'verification' => $verificationSummary,
            'mfa' => $user->mfa_enabled ? 'Enabled' : 'Disabled',
            'login_attempts' => (string) $user->login_attempts,
            'last_login' => $user->last_login_at?->format('Y-m-d H:i') ?? 'Never',
            'locked_until' => $user->locked_until?->format('Y-m-d H:i') ?? 'Not locked',
            'profile' => $profile,
            'specialty' => $this->profileSpecialty($user, $profile),
            'experience' => $this->profileExperience($user, $profile),
        ];

        $view = match ($user->role) {
            'partner' => 'iam.users.show-partner',
            'admin', 'moderator' => 'iam.users.show-admin',
            default => 'iam.users.show-engineer',
        };

        return view($view, [
            'user' => $user,
            'detail' => $detail,
        ]);
    }

    private function baseUserQuery(string $memberView): Builder
    {
        $query = User::query()
            ->leftJoin('engineer_profiles', 'engineer_profiles.user_id', '=', 'users.id')
            ->leftJoin('unverified_member_profiles', 'unverified_member_profiles.user_id', '=', 'users.id')
            ->leftJoin('partner_profiles', 'partner_profiles.user_id', '=', 'users.id')
            ->leftJoin('plant_types as partner_plant_types', 'partner_plant_types.id', '=', 'partner_profiles.plant_type_id')
            ->select('users.*')
            ->addSelect([
                'engineer_profiles.industry_specialization as engineer_industry_specialization',
                'engineer_profiles.expertise_tags as engineer_expertise_tags',
                'engineer_profiles.experience_years as engineer_experience_years',
                'unverified_member_profiles.field_of_study as unverified_field_of_study',
                'unverified_member_profiles.expertise_tags as unverified_expertise_tags',
                'unverified_member_profiles.experience_years as unverified_experience_years',
                'partner_profiles.keywords as partner_keywords',
                'partner_profiles.founded_year as partner_founded_year',
                'partner_profiles.approval_status as partner_approval_status',
                'partner_plant_types.name as partner_plant_type_name',
            ]);

        if ($this->hasProfilePlantTypePivots()) {
            $query
                ->selectSub(
                    $this->plantTypeNamesSubquery('engineer_profile_plant_type', 'engineer_profile_id', 'engineer_profiles.id'),
                    'engineer_plant_type_names'
                )
                ->selectSub(
                    $this->plantTypeNamesSubquery('unverified_member_profile_plant_type', 'unverified_member_profile_id', 'unverified_member_profiles.id'),
                    'unverified_plant_type_names'
                );
        } else {
            $query->addSelect([
                DB::raw('null as engineer_plant_type_names'),
                DB::raw('null as unverified_plant_type_names'),
            ]);
        }

        return $this->scopeMemberView($query, $memberView);
    }

    private function hasProfilePlantTypePivots(): bool
    {
        return Schema::hasTable('engineer_profile_plant_type')
            && Schema::hasTable('unverified_member_profile_plant_type');
    }

    private function plantTypeNamesSubquery(string $pivotTable, string $profileColumn, string $profileReference): string
    {
        $aggregate = DB::connection()->getDriverName() === 'sqlite'
            ? "group_concat(plant_types.name, ', ')"
            : "string_agg(plant_types.name, ', ' order by {$pivotTable}.sort_order, plant_types.name)";

        return "select {$aggregate} from {$pivotTable} inner join plant_types on plant_types.id = {$pivotTable}.plant_type_id where {$pivotTable}.{$profileColumn} = {$profileReference}";
    }

    private function scopeMemberView(Builder $query, string $memberView): Builder
    {
        return match ($memberView) {
            'partners' => $query->where('users.role', 'partner'),
            'administrators' => $query->whereIn('users.role', ['admin', 'moderator']),
            default => $query->whereIn('users.role', ['professional', 'unverified_member']),
        };
    }

    private function applyPlantTypeFilter(Builder $query, string $memberView, string $plantTypeId): void
    {
        if ($plantTypeId === '' || $memberView === 'administrators') {
            return;
        }

        $plantTypeId = (int) $plantTypeId;

        match ($memberView) {
            'partners' => $query->where('partner_profiles.plant_type_id', $plantTypeId),
            default => $this->hasProfilePlantTypePivots()
                ? $query->where(function (Builder $query) use ($plantTypeId): void {
                    $query->whereExists(function ($query) use ($plantTypeId): void {
                        $query->selectRaw('1')
                            ->from('engineer_profile_plant_type')
                            ->whereColumn('engineer_profile_plant_type.engineer_profile_id', 'engineer_profiles.id')
                            ->where('engineer_profile_plant_type.plant_type_id', $plantTypeId);
                    })->orWhereExists(function ($query) use ($plantTypeId): void {
                        $query->selectRaw('1')
                            ->from('unverified_member_profile_plant_type')
                            ->whereColumn('unverified_member_profile_plant_type.unverified_member_profile_id', 'unverified_member_profiles.id')
                            ->where('unverified_member_profile_plant_type.plant_type_id', $plantTypeId);
                    });
                })
                : $query->whereRaw('0 = 1'),
        };
    }
    private function applyTabScope(Builder $query, string $memberView, string $tab): void
    {
        match ($tab) {
            'pending' => $this->scopePending($query, $memberView),
            'frozen' => $query->where('users.status', 'frozen'),
            'suspended' => $query->where('users.status', 'suspended'),
            default => $query->where('users.status', 'active'),
        };
    }

    private function scopePending(Builder $query, string $memberView): void
    {
        match ($memberView) {
            'partners' => $query->where('partner_profiles.approval_status', 'pending'),
            'administrators' => $query->where('users.is_verified', false),
            default => $query->where(function (Builder $query): void {
                $query->where('users.is_verified', false)
                    ->orWhereHas('verificationRequests', fn (Builder $query) => $query->where('status', 'pending'));
            }),
        };
    }

    private function statsFor(string $memberView): array
    {
        $totalUsers = $this->baseUserQuery($memberView)->count('users.id');
        $activeMembers = $this->baseUserQuery($memberView)->where('users.status', 'active')->count('users.id');
        $pendingApprovals = $this->baseUserQuery($memberView);
        $this->scopePending($pendingApprovals, $memberView);
        $frozenUsers = $this->baseUserQuery($memberView)->where('users.status', 'frozen')->count('users.id');
        $suspendedUsers = $this->baseUserQuery($memberView)->where('users.status', 'suspended')->count('users.id');

        return [
            'total_users' => $totalUsers,
            'active_members' => $activeMembers,
            'pending_approvals' => $pendingApprovals->count('users.id'),
            'frozen_users' => $frozenUsers,
            'suspended_users' => $suspendedUsers,
        ];
    }

    private function memberView(string $requestedView, string $navigationRole = ''): string
    {
        if (in_array($requestedView, ['engineers', 'partners', 'administrators'], true)) {
            return $requestedView;
        }

        return match ($navigationRole) {
            'partner' => 'partners',
            'admin', 'moderator' => 'administrators',
            default => 'engineers',
        };
    }

    private function pageTitle(string $memberView): string
    {
        return match ($memberView) {
            'partners' => 'Partner Management',
            'administrators' => 'Administrator Management',
            default => 'Engineer Management',
        };
    }

    private function displayName(User $user): string
    {
        $name = trim(implode(' ', array_filter([$user->first_name, $user->last_name])));

        return $name !== '' ? $name : ($user->username ?: $user->email);
    }

    private function plantTypeLabel(User $user): string
    {
        $value = match ($user->role) {
            'professional' => $user->engineer_plant_type_names,
            'unverified_member' => $user->unverified_plant_type_names,
            'partner' => $user->partner_plant_type_name,
            default => null,
        };

        return $value ?: ($user->role === 'admin' || $user->role === 'moderator' ? 'Not applicable' : 'No plant type');
    }

    private function experienceLabel(User $user): string
    {
        $years = match ($user->role) {
            'professional' => $user->engineer_experience_years,
            'unverified_member' => $user->unverified_experience_years,
            default => null,
        };

        if ($years !== null && $years !== '') {
            return sprintf('%s years', $years);
        }

        if ($user->role === 'partner' && $user->partner_founded_year) {
            return sprintf('Founded %s', $user->partner_founded_year);
        }

        return 'No profile yet';
    }

    private function securityLabel(User $user): string
    {
        $signals = [];

        $signals[] = $user->mfa_enabled ? 'MFA enabled' : 'MFA disabled';

        if ((int) $user->login_attempts > 0) {
            $signals[] = sprintf('%s failed login%s', $user->login_attempts, (int) $user->login_attempts === 1 ? '' : 's');
        }

        if ($user->locked_until !== null) {
            $signals[] = 'Locked';
        }

        return implode(' | ', $signals);
    }
    private function profileFor(User $user): ?object
    {
        return match ($user->role) {
            'professional' => DB::table('engineer_profiles')->where('user_id', $user->id)->first(),
            'unverified_member' => DB::table('unverified_member_profiles')->where('user_id', $user->id)->first(),
            'partner' => DB::table('partner_profiles')->where('user_id', $user->id)->first(),
            default => null,
        };
    }

    private function profileSpecialty(User $user, ?object $profile): string
    {
        if (! $profile) {
            return 'No profile yet';
        }

        return match ($user->role) {
            'professional' => $this->jsonLabel($profile->industry_specialization ?? null) ?: $this->jsonLabel($profile->expertise_tags ?? null) ?: 'No profile yet',
            'unverified_member' => ($profile->field_of_study ?? null) ?: $this->jsonLabel($profile->expertise_tags ?? null) ?: 'No profile yet',
            'partner' => $this->jsonLabel($profile->keywords ?? null) ?: 'No profile yet',
            default => 'No profile yet',
        };
    }

    private function profileExperience(User $user, ?object $profile): string
    {
        if (! $profile) {
            return 'No profile yet';
        }

        $years = match ($user->role) {
            'professional', 'unverified_member' => $profile->experience_years ?? null,
            default => null,
        };

        if ($years !== null && $years !== '') {
            return sprintf('%s years', $years);
        }

        if ($user->role === 'partner' && ($profile->founded_year ?? null)) {
            return sprintf('Founded %s', $profile->founded_year);
        }

        return 'No profile yet';
    }

    private function verificationSummary(User $user): string
    {
        $pending = VerificationRequest::where('user_id', $user->id)->where('status', 'pending')->count();

        if ($pending > 0) {
            return sprintf('%s pending request%s', $pending, $pending === 1 ? '' : 's');
        }

        return $user->is_verified ? 'Verified' : 'Not verified';
    }

    private function statusLabel(User $user): string
    {
        return match ($user->status) {
            'active' => 'Active',
            'frozen' => 'Frozen',
            'suspended' => 'Suspended',
            default => ucfirst((string) $user->status),
        };
    }

    private function statusBadge(User $user): string
    {
        return match ($user->status) {
            'active' => 'text-bg-success',
            'frozen' => 'text-bg-warning',
            'suspended' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };
    }

    private function jsonLabel(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            $flat = collect($value)
                ->flatten()
                ->filter(fn ($item) => is_scalar($item) && trim((string) $item) !== '')
                ->map(fn ($item) => trim((string) $item))
                ->unique()
                ->take(3)
                ->values();

            return $flat->isNotEmpty() ? $flat->implode(', ') : null;
        }

        return trim((string) $value) ?: null;
    }

    /**
     * @return array<int, string>
     */
    private function plantTypeOptions(): array
    {
        return PlantType::query()
            ->active()
            ->sorted()
            ->pluck('name', 'id')
            ->all();
    }
}







