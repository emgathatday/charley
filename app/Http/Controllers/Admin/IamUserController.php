<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EngineerProfile;
use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\PlantType;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $profilePhotoMediaId = match ($user->role) {
            'professional' => $user->engineer_photo_media_id ?? null,
            'unverified_member' => $user->unverified_photo_media_id ?? $user->engineer_photo_media_id ?? null,
            default => null,
        };
        $user->profile_photo_url = $profilePhotoMediaId ? $this->profilePhotoUrl((object) ['photo_media_id' => $profilePhotoMediaId]) : null;
            $user->latest_verification_status = $user->pending_verification_requests_count > 0
                ? 'Pending approval'
                : ($user->is_verified ? 'Verified' : 'Not verified');

            if ($user->role === 'partner' && ($user->partner_approval_status ?? null)) {
                $user->latest_verification_status = str_replace('_', ' ', ucfirst((string) $user->partner_approval_status));
                $this->decoratePartnerSubscription($user);
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
            'joined' => $user->created_at?->format('M j, Y') ?? 'Unknown',
            'verified_at' => $user->verified_at?->format('M j, Y') ?? 'Not verified',
            'verification_due' => $user->verification_expires_at?->format('M j, Y') ?? 'Not scheduled',
            'profile' => $profile,
            'specialty' => $this->profileSpecialty($user, $profile),
            'experience' => $this->profileExperience($user, $profile),
            'plant_focus' => $this->profilePlantFocus($user, $profile),
            'security' => $this->securityDetail($user),
            'activity' => $this->activityDetail($user),
            'metas' => $this->metaDetail($user),
        ];

        $view = match ($user->role) {
            'partner' => 'iam.users.show-partner',
            'admin', 'moderator' => 'iam.users.show-admin',
            default => 'iam.users.show-engineer',
        };

        return view($view, [
            'user' => $user,
            'detail' => $detail,
            'partnerLogoUrl' => $user->role === 'partner' ? $this->partnerLogoUrl($profile) : null,
            'profilePhotoUrl' => in_array($user->role, ['professional', 'unverified_member'], true) ? $this->profilePhotoUrl($profile) : null,
        ]);
    }

    public function engineers(Request $request): View
    {
        return $this->managementView($request, 'engineers', 'iam.users.engineers');
    }

    public function partners(Request $request): View
    {
        return $this->managementView($request, 'partners', 'iam.users.partners');
    }

    private function managementView(Request $request, string $memberView, string $view): View
    {
        $filters = [
            'keyword' => trim((string) $request->input('keyword', $request->input('search', ''))),
            'plant_type_id' => (string) $request->input('plant_type_id', ''),
            'tab' => (string) $request->input('tab', 'all'),
            'account_type' => (string) $request->input('account_type', 'all'),
            'status' => (string) $request->input('status', 'all'),
            'subscription_tier_id' => (string) $request->input('subscription_tier_id', ''),
        ];

        $usersQuery = $this->baseUserQuery($memberView)
            ->withCount([
                'verificationRequests',
                'verificationRequests as pending_verification_requests_count' => fn (Builder $query) => $query->where('status', 'pending'),
            ])
            ->when($filters['keyword'] !== '', function (Builder $query) use ($filters, $memberView): void {
                $keyword = strtolower($filters['keyword']);
                $query->where(function (Builder $query) use ($keyword, $memberView): void {
                    $query->whereRaw('lower(users.username) like ?', ["%{$keyword}%"])
                        ->orWhereRaw('lower(users.first_name) like ?', ["%{$keyword}%"])
                        ->orWhereRaw('lower(users.last_name) like ?', ["%{$keyword}%"])
                        ->orWhereRaw("lower(coalesce(users.first_name, '') || ' ' || coalesce(users.last_name, '')) like ?", ["%{$keyword}%"])
                        ->orWhereRaw('lower(users.email) like ?', ["%{$keyword}%"]);
                    if ($memberView === 'engineers') {
                        $query->orWhereRaw('lower(engineer_profiles.current_company) like ?', ["%{$keyword}%"])
                            ->orWhereRaw('lower(engineer_profiles.current_institution) like ?', ["%{$keyword}%"])
                            ->orWhereRaw('lower(engineer_profiles.position) like ?', ["%{$keyword}%"])
                            ->orWhereRaw('lower(engineer_profiles.field_of_study) like ?', ["%{$keyword}%"])
                            ->orWhereRaw('lower(engineer_profiles.plant_name) like ?', ["%{$keyword}%"])
                            ->orWhereRaw('lower(engineer_profiles.expertise_tags) like ?', ["%{$keyword}%"])
                            ->orWhereRaw('lower(engineer_profiles.industry_specialization) like ?', ["%{$keyword}%"])
                            ->orWhereRaw('lower(engineer_profiles.searchable_keywords) like ?', ["%{$keyword}%"]);
                    }
                    if ($memberView === 'partners') {
                        $query->orWhereRaw('lower(partner_profiles.company_name) like ?', ["%{$keyword}%"])
                            ->orWhereRaw('lower(partner_profiles.website) like ?', ["%{$keyword}%"])
                            ->orWhereRaw('lower(partner_profiles.country) like ?', ["%{$keyword}%"]);
                    }
                });
            });

        $this->applyPlantTypeFilter($usersQuery, $memberView, $filters['plant_type_id']);
        $this->applyManagementFilters($usersQuery, $memberView, $filters);

        if ($memberView === 'partners' && $filters['subscription_tier_id'] !== '') {
            $usersQuery->whereExists(function ($query) use ($filters): void {
                $query->selectRaw('1')->from('partner_subscriptions')
                    ->whereColumn('partner_subscriptions.user_id', 'users.id')
                    ->where('partner_subscriptions.status', 'active')
                    ->where('partner_subscriptions.tier_id', (int) $filters['subscription_tier_id']);
            });
        }

        if ($filters['tab'] !== 'all') {
            $this->applyTabScope($usersQuery, $memberView, $filters['tab']);
        }

        $users = $usersQuery->latest('users.created_at')->paginate(10)->withQueryString();
        $users->getCollection()->each(fn (User $user) => $this->decorateUser($user));

        return view($view, [
            'users' => $users,
            'stats' => $this->statsFor($memberView),
            'filters' => $filters,
            'plantTypeOptions' => $this->plantTypeOptions(),
            'subscriptionTierOptions' => $this->subscriptionTierOptions(),
            'tierStats' => $memberView === 'partners' ? $this->partnerTierStats() : [],
        ]);
    }

    public function createPartner(): View
    {
        $canLoadTierPermissions = Schema::hasTable('subscription_tier_permissions') && Schema::hasTable('subscription_permissions');
        $query = SubscriptionTier::query()->active()->orderBy('sort_order')->orderBy('display_name');
        if ($canLoadTierPermissions) {
            $query->with('tierPermissions.permission');
        }
        $subscriptionTiers = $query->get();
        if (! $canLoadTierPermissions) {
            $subscriptionTiers->each(fn (SubscriptionTier $tier) => $tier->setRelation('tierPermissions', collect()));
        }

        return view('iam.users.create-partner', [
            'subscriptionTiers' => $subscriptionTiers,
            'plantTypeOptions' => $this->plantTypeOptions(),
        ]);
    }

    public function storePartner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'temporary_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'activate_account' => ['nullable', 'boolean'],
            'require_email_verification' => ['nullable', 'boolean'],
            'subscription_tier_id' => ['required', Rule::exists('subscription_tiers', 'id')->where('is_active', true)],
            'auto_renew' => ['nullable', 'boolean'],
            'subscription_starts_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date', 'after_or_equal:subscription_starts_at'],
            'payment_amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', Rule::in(['bank_transfer', 'manual_invoice', 'other'])],
            'payment_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'refunded'])],
            'transaction_code' => ['nullable', 'string', 'max:255'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'company_overview' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'public_contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'plant_type_id' => ['nullable', 'integer'],
            'keywords' => ['nullable', 'json'],
            'logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
        ]);

        $keywords = $request->has('keywords') ? $this->keywordList($data['keywords'] ?? null) : null;
        if ($request->has('keywords') && $keywords === []) {
            throw ValidationException::withMessages([
                'keywords' => 'Add at least one keyword.',
            ]);
        }
        $data['keywords'] = $keywords;

        $tier = SubscriptionTier::query()->active()->findOrFail($data['subscription_tier_id']);
        $activateAccount = $request->boolean('activate_account');
        $requiresEmailVerification = $request->boolean('require_email_verification');
        $adminId = $request->user()?->id;
        $logoFile = $request->file('logo_file');

        [$user, $subscription] = DB::transaction(function () use ($data, $tier, $activateAccount, $requiresEmailVerification, $adminId, $logoFile): array {
            $startsAt = $this->subscriptionStartDate($data['subscription_starts_at'] ?? null, $activateAccount);
            $endsAt = $this->subscriptionEndDate($data['subscription_ends_at'] ?? null, $startsAt, $tier);
            $isVerified = ! $requiresEmailVerification;

            $user = User::create([
                'username' => $data['username'] ?? $this->uniqueUsername($data['email'], $data['company_name']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['temporary_password'] ?? Str::password(16)),
                'role' => 'partner',
                'is_verified' => $isVerified,
                'verified_at' => $isVerified ? now() : null,
                'verification_expires_at' => null,
                'status' => $activateAccount ? 'active' : 'frozen',
                'login_attempts' => 0,
                'mfa_enabled' => false,
            ]);

            $subscription = PartnerSubscription::create([
                'user_id' => $user->id,
                'tier_id' => $tier->id,
                'status' => $activateAccount ? 'active' : 'pending_approval',
                'auto_renew' => (bool) ($data['auto_renew'] ?? false),
                'approved_by' => $activateAccount ? $adminId : null,
                'approved_at' => $activateAccount ? now() : null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $logoMedia = $this->storePartnerLogo($logoFile, $adminId);
            $profile = PartnerProfile::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'logo_media_id' => $logoMedia?->id,
                'overview' => $data['company_overview'] ?? null,
                'active_partner_subscription_id' => $activateAccount ? $subscription->id : null,
                'plant_type_id' => $data['plant_type_id'] ?? null,
                'keywords' => $data['keywords'],
                'contact_email' => $data['public_contact_email'] ?? $data['email'],
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'website' => $data['website'] ?? null,
                'layout_template' => 'layout_1',
                'feed_highlight_enabled' => true,
                'subscription_status' => $subscription->status,
                'subscription_expires_at' => $subscription->ends_at,
                'approval_status' => $activateAccount ? 'approved' : 'pending',
                'verified_at' => $activateAccount ? now() : null,
            ]);
            $logoMedia?->forceFill(['attachable_type' => PartnerProfile::class, 'attachable_id' => $profile->id, 'is_orphan' => false])->save();

            if (($data['payment_amount'] ?? null) !== null && $data['payment_amount'] !== '') {
                $paymentStatus = $data['payment_status'] ?? 'pending';
                SubscriptionPayment::create([
                    'partner_subscription_id' => $subscription->id,
                    'amount' => $data['payment_amount'],
                    'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                    'period_start' => $data['period_start'] ?? $startsAt?->toDateString(),
                    'period_end' => $data['period_end'] ?? $endsAt?->toDateString(),
                    'status' => $paymentStatus,
                    'transaction_code' => $data['transaction_code'] ?? null,
                    'approved_by' => $paymentStatus === 'approved' ? $adminId : null,
                ]);
            }

            return [$user, $subscription];
        });

        return redirect()->route('admin.dashboard.iam.users.show', $user)->with('status', 'Partner account created.')->with('subscription_id', $subscription->id);
    }

    public function createEngineer(): View
    {
        return view('iam.users.create-engineer', [
            'plantTypeOptions' => $this->plantTypeOptions(),
            'knowledgeDomainsByPlantType' => $this->knowledgeDomainsByPlantType(),
        ]);
    }

    public function createAdmin(): View
    {
        return view('iam.users.create-admin');
    }

    public function editEngineer(User $user): View
    {
        $profile = $this->profileFor($user);

        return view('iam.users.edit-engineer', [
            'user' => $user,
            'profile' => $profile,
            'profilePhotoUrl' => $this->profilePhotoUrl($profile),
            'plantTypeOptions' => $this->plantTypeOptions(),
            'selectedPlantTypeIds' => $this->engineerProfilePlantTypeIds($user, $profile),
            'latestVerificationRequest' => Schema::hasTable('verification_requests')
                ? VerificationRequest::query()->where('user_id', $user->id)->latest('id')->first()
                : null,
        ]);
    }

    public function editPartner(User $user): View
    {
        if ($user->role !== 'partner') {
            abort(404);
        }

        $profile = $this->profileFor($user);

        return view('iam.users.edit-partner', [
            'user' => $user,
            'profile' => $profile,
            'partnerLogoUrl' => $this->partnerLogoUrl($profile),
            'plantTypeOptions' => $this->plantTypeOptions(),
            'subscriptionTiers' => SubscriptionTier::query()->active()->orderBy('sort_order')->orderBy('display_name')->get(),
        ]);
    }

    public function adminProfile(Request $request): View
    {
        return view('iam.users.admin-profile', ['admin' => $request->user(), 'displayName' => $this->displayName($request->user()), 'initials' => 'AD', 'profileTitle' => 'Platform Administrator', 'organisation' => 'Charley Platform', 'timezone' => config('app.timezone'), 'sessions' => collect(), 'latestSession' => null]);
    }

    public function storeEngineer(Request $request): RedirectResponse
    {
        $plantTypeIds = array_keys($this->plantTypeOptions());
        $data = $request->validate([
            'account_type' => ['required', Rule::in(['member', 'professional'])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'frozen'])],
            'temporary_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'current_company' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'current_institution' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'plant_name' => ['nullable', 'string', 'max:255'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'phone' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'expertise_tags' => ['nullable', 'string'],
            'industry_specialization' => ['nullable', 'string'],
            'searchable_keywords' => ['nullable', 'string'],
            'verification_intent' => ['nullable', 'boolean'],
            'plant_type_ids' => ['nullable', 'array'],
            'plant_type_ids.*' => ['integer', Rule::in($plantTypeIds)],
            'primary_plant_type_id' => ['nullable', 'integer', Rule::in($plantTypeIds)],
        ]);

        $user = DB::transaction(function () use ($data, $request): User {
            $role = $data['account_type'] === 'professional' ? 'professional' : 'unverified_member';
            $isVerified = $role === 'professional';
            $user = User::create([
                'username' => $data['username'] ?? $this->uniqueUsername($data['email'], trim($data['first_name'].' '.$data['last_name'])),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['temporary_password'] ?? Str::password(16)),
                'role' => $role,
                'is_verified' => $isVerified,
                'verified_at' => $isVerified ? now() : null,
                'verification_expires_at' => $isVerified ? now()->addYear() : null,
                'status' => $data['status'] ?? 'active',
                'login_attempts' => 0,
                'mfa_enabled' => false,
            ]);

            if (Schema::hasTable('engineer_profiles')) {
                $profile = EngineerProfile::create([
                    'user_id' => $user->id,
                    'current_company' => $data['current_company'] ?? $data['company'] ?? null,
                    'current_institution' => $data['current_institution'] ?? null,
                    'position' => $data['position'] ?? null,
                    'field_of_study' => $data['field_of_study'] ?? null,
                    'plant_name' => $data['plant_name'] ?? null,
                    'experience_years' => $data['years_experience'] ?? null,
                    'expertise_tags' => $this->commaSeparatedArray($data['expertise_tags'] ?? null),
                    'industry_specialization' => $this->commaSeparatedArray($data['industry_specialization'] ?? null),
                    'searchable_keywords' => $this->commaSeparatedArray($data['searchable_keywords'] ?? null),
                    'phone' => $data['phone'] ?? null,
                    'linkedin_url' => $data['linkedin_url'] ?? null,
                    'verification_intent' => $request->boolean('verification_intent'),
                ]);
                $this->syncEngineerPlantTypes((int) $profile->id, $data['plant_type_ids'] ?? [], $data['primary_plant_type_id'] ?? null);
            }

            return $user;
        });

        return redirect()->route('admin.dashboard.iam.users.show', $user)->with('status', 'Engineer account created.');
    }

    public function updateEngineer(Request $request, User $user): RedirectResponse
    {
        if (! in_array($user->role, ['professional', 'unverified_member'], true)) {
            abort(404);
        }

        $plantTypeIds = array_keys($this->plantTypeOptions());
        $plantTypeRules = ['nullable', 'array'];
        if ($plantTypeIds !== []) {
            $plantTypeRules = ['required', 'array', 'min:1'];
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'position' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:300'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'current_company' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'job_availability' => ['nullable', Rule::in(['not_looking', 'open_to_opportunities', 'open'])],
            'education' => ['nullable', 'string'],
            'plant_type_ids' => $plantTypeRules,
            'plant_type_ids.*' => ['integer', Rule::in($plantTypeIds)],
            'expertise_tags' => ['nullable', 'string'],
            'searchable_keywords' => ['nullable', 'string'],
            'industry_specialization' => ['nullable', 'string'],
            'account_type' => ['required', Rule::in(['member', 'professional'])],
            'status' => ['required', Rule::in(['active', 'suspended', 'frozen'])],
            'is_discoverable' => ['nullable', 'boolean'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        DB::transaction(function () use ($data, $request, $user): void {
            $role = $data['account_type'] === 'professional' ? 'professional' : 'unverified_member';
            $photoFile = $request->file('profile_photo');
            $verifiedAt = $role === 'professional' ? ($user->verified_at ?? now()) : null;

            $user->forceFill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'role' => $role,
                'status' => $data['status'],
                'is_verified' => $role === 'professional',
                'verified_at' => $verifiedAt,
                'verification_expires_at' => $role === 'professional'
                    ? ($user->verification_expires_at ?? $verifiedAt?->copy()->addYear())
                    : null,
            ])->save();

            if (! Schema::hasTable('engineer_profiles')) {
                return;
            }

            $profile = DB::table('engineer_profiles')->where('user_id', $user->id)->first();
            $profileData = [
                'bio' => $data['bio'] ?? null,
                'current_company' => $data['current_company'] ?? null,
                'position' => $data['position'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
                'education' => $data['education'] ?? null,
                'expertise_tags' => $this->commaSeparatedList($data['expertise_tags'] ?? null),
                'industry_specialization' => $this->commaSeparatedList($data['industry_specialization'] ?? null),
                'searchable_keywords' => $this->commaSeparatedList($data['searchable_keywords'] ?? null),
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'job_availability' => $data['job_availability'] ?? null,
                'is_discoverable' => $request->boolean('is_discoverable'),
                'updated_at' => now(),
            ];

            if ($profile) {
                DB::table('engineer_profiles')->where('id', $profile->id)->update($profileData);
                $profileId = (int) $profile->id;
            } else {
                $profileData['user_id'] = $user->id;
                $profileData['created_at'] = now();
                $profileId = (int) DB::table('engineer_profiles')->insertGetId($profileData);
            }

            if ($photoFile instanceof UploadedFile) {
                $photoMedia = $this->storeEngineerProfilePhoto($photoFile, $request->user()?->id);
                $this->bindEngineerProfilePhoto($user, $role, $profileId, $photoMedia);
            }

            if (Schema::hasTable('engineer_profile_plant_type')) {
                $selectedPlantTypeIds = collect($data['plant_type_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
                DB::table('engineer_profile_plant_type')->where('engineer_profile_id', $profileId)->delete();
                $selectedPlantTypeIds->each(function (int $plantTypeId, int $index) use ($profileId): void {
                    DB::table('engineer_profile_plant_type')->insert([
                        'engineer_profile_id' => $profileId,
                        'plant_type_id' => $plantTypeId,
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
            }
        });

        return redirect()->route('admin.dashboard.iam.users.show', $user)->with('status', 'Engineer profile updated.');
    }

    public function updatePartner(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'partner') {
            abort(404);
        }

        $plantTypeIds = array_keys($this->plantTypeOptions());
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'company_overview' => ['nullable', 'string', 'max:400'],
            'plant_type_id' => ['nullable', 'integer', Rule::in($plantTypeIds)],
            'country' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
            'approval_status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'suspended'])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'public_contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'layout_template' => ['required', Rule::in(['layout_1', 'layout_2', 'layout_3'])],
            'feed_highlight_enabled' => ['nullable', 'boolean'],
            'subscription_tier_id' => ['nullable', Rule::exists('subscription_tiers', 'id')->where('is_active', true)],
            'subscription_status' => ['required', Rule::in(['inactive', 'pending_approval', 'active', 'suspended', 'expired', 'cancelled'])],
            'status' => ['required', Rule::in(['active', 'suspended', 'frozen'])],
            'logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
        ]);

        DB::transaction(function () use ($data, $request, $user): void {
            $isApproved = $data['approval_status'] === 'approved';
            $logoFile = $request->file('logo_file');

            $user->forceFill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'username' => $data['username'] ?: $user->username,
                'role' => 'partner',
                'status' => $data['status'],
                'is_verified' => $isApproved,
                'verified_at' => $isApproved ? ($user->verified_at ?? now()) : null,
                'verification_expires_at' => null,
            ])->save();

            if (! Schema::hasTable('partner_profiles')) {
                return;
            }

            $subscription = null;
            $subscriptionStatus = $data['subscription_status'];
            $profile = PartnerProfile::query()->where('user_id', $user->id)->first();
            if (($data['subscription_tier_id'] ?? null) !== null && $data['subscription_tier_id'] !== '') {
                $subscription = PartnerSubscription::query()
                    ->where('user_id', $user->id)
                    ->when($profile?->active_partner_subscription_id, fn (Builder $query, int $subscriptionId) => $query->where('id', $subscriptionId))
                    ->latest('id')
                    ->first();

                if (! $subscription) {
                    $subscription = PartnerSubscription::query()->create([
                        'user_id' => $user->id,
                        'tier_id' => (int) $data['subscription_tier_id'],
                        'status' => $subscriptionStatus === 'inactive' ? 'pending_approval' : $subscriptionStatus,
                        'approved_by' => $isApproved ? $request->user()?->id : null,
                        'approved_at' => $isApproved ? now() : null,
                        'starts_at' => $subscriptionStatus === 'active' ? now()->startOfDay() : null,
                        'ends_at' => null,
                        'auto_renew' => false,
                    ]);
                } else {
                    $subscription->forceFill([
                        'tier_id' => (int) $data['subscription_tier_id'],
                        'status' => $subscriptionStatus === 'inactive' ? 'pending_approval' : $subscriptionStatus,
                        'approved_by' => $isApproved ? ($subscription->approved_by ?? $request->user()?->id) : $subscription->approved_by,
                        'approved_at' => $isApproved ? ($subscription->approved_at ?? now()) : $subscription->approved_at,
                        'starts_at' => $subscriptionStatus === 'active' ? ($subscription->starts_at ?? now()->startOfDay()) : $subscription->starts_at,
                    ])->save();
                }
            }

            $profileData = [
                'company_name' => $data['company_name'],
                'overview' => $data['company_overview'] ?? null,
                'plant_type_id' => $data['plant_type_id'] ?? null,
                'keywords' => $this->commaSeparatedArray($data['keywords'] ?? null),
                'contact_email' => $data['public_contact_email'] ?? $data['email'],
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'website' => $data['website'] ?? null,
                'founded_year' => $data['founded_year'] ?? null,
                'layout_template' => $data['layout_template'],
                'feed_highlight_enabled' => $request->boolean('feed_highlight_enabled'),
                'subscription_status' => $subscription ? $subscription->status : 'inactive',
                'subscription_expires_at' => $subscription?->ends_at,
                'approval_status' => $data['approval_status'],
                'verified_at' => $isApproved ? ($profile?->verified_at ?? now()) : null,
                'active_partner_subscription_id' => $subscription?->id,
            ];

            if ($logoFile instanceof UploadedFile) {
                $logoMedia = $this->storePartnerLogo($logoFile, $request->user()?->id);
                $profileData['logo_media_id'] = $logoMedia?->id;
            }

            if ($profile) {
                $profile->forceFill($profileData)->save();
            } else {
                $profile = PartnerProfile::query()->create($profileData + ['user_id' => $user->id]);
            }

            if (isset($logoMedia) && $logoMedia) {
                $logoMedia->forceFill([
                    'attachable_type' => PartnerProfile::class,
                    'attachable_id' => $profile->id,
                    'is_orphan' => false,
                ])->save();
            }
        });

        return redirect()->route('admin.dashboard.iam.users.show', $user)->with('status', 'Partner profile updated.');
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        return redirect()->route('admin.dashboard.iam.users');
    }

    private function decorateUser(User $user): void
    {
        $user->display_id = (string) $user->id;
        $user->display_name = $this->displayName($user);
        $user->plant_type_label = $this->plantTypeLabel($user);
        $user->experience_label = $this->experienceLabel($user);
        $user->security_label = $this->securityLabel($user);
        $user->status_label = $this->statusLabel($user);
        $user->status_badge = $this->statusBadge($user);
        $profilePhotoMediaId = match ($user->role) {
            'professional' => $user->engineer_photo_media_id ?? null,
            'unverified_member' => $user->unverified_photo_media_id ?? $user->engineer_photo_media_id ?? null,
            default => null,
        };
        $user->profile_photo_url = $profilePhotoMediaId ? $this->profilePhotoUrl((object) ['photo_media_id' => $profilePhotoMediaId]) : null;
        $user->latest_verification_status = $user->pending_verification_requests_count > 0 ? 'Pending approval' : ($user->is_verified ? 'Verified' : 'Not verified');

        if ($user->role === 'partner') {
            $user->latest_verification_status = $user->partner_approval_status ? str_replace('_', ' ', ucfirst((string) $user->partner_approval_status)) : $user->latest_verification_status;
            $this->decoratePartnerSubscription($user);
        }
    }

    private function decoratePartnerSubscription(User $user): void
    {
        $subscription = PartnerSubscription::query()
            ->with('tier')
            ->active()
            ->where('user_id', $user->id)
            ->when($user->partner_active_subscription_id ?? null, fn (Builder $query, int $subscriptionId) => $query->orderByRaw('case when id = ? then 0 else 1 end', [$subscriptionId]))
            ->latest('starts_at')
            ->latest('id')
            ->first();

        $user->partner_active_subscription_id = $subscription?->id ?? ($user->partner_active_subscription_id ?? null);
        $user->partner_tier_label = $subscription?->tier?->display_name ?? 'No active tier';
        $user->partner_tier_code = $subscription?->tier?->code ?? 'inactive';
        $user->partner_subscription_status = $subscription?->status ?? ($user->partner_subscription_status ?? 'inactive');
        $user->partner_subscription_expires_at = $subscription?->ends_at ?? ($user->partner_subscription_expires_at ?? null);
    }

    private function baseUserQuery(string $memberView): Builder
    {
        $hasEngineerProfiles = Schema::hasTable('engineer_profiles');
        $hasUnverifiedProfiles = Schema::hasTable('unverified_member_profiles');
        $hasPartnerProfiles = Schema::hasTable('partner_profiles');
        $hasPlantTypes = Schema::hasTable('plant_types');
        $hasEngineerProfilePhoto = $hasEngineerProfiles && Schema::hasColumn('engineer_profiles', 'photo_media_id');
        $hasUnverifiedProfilePhoto = $hasUnverifiedProfiles && Schema::hasColumn('unverified_member_profiles', 'photo_media_id');

        $query = User::query()->select('users.*');

        if ($hasEngineerProfiles) {
            $query->leftJoin('engineer_profiles', 'engineer_profiles.user_id', '=', 'users.id')
                ->addSelect([
                    'engineer_profiles.industry_specialization as engineer_industry_specialization',
                    'engineer_profiles.expertise_tags as engineer_expertise_tags',
                    'engineer_profiles.field_of_study as unverified_field_of_study',
                    'engineer_profiles.expertise_tags as unverified_expertise_tags',
                    'engineer_profiles.experience_years as engineer_experience_years',
                    'engineer_profiles.experience_years as unverified_experience_years',
                    $hasEngineerProfilePhoto ? 'engineer_profiles.photo_media_id as engineer_photo_media_id' : DB::raw('null as engineer_photo_media_id'),
                ]);
        } else {
            $query->addSelect([
                DB::raw('null as engineer_industry_specialization'),
                DB::raw('null as engineer_expertise_tags'),
                DB::raw('null as engineer_experience_years'),
                DB::raw('null as engineer_photo_media_id'),
            ]);
        }

        if ($hasUnverifiedProfiles && $hasEngineerProfiles) {
            $query->leftJoin('unverified_member_profiles', 'unverified_member_profiles.user_id', '=', 'users.id')
                ->addSelect([
                    $hasUnverifiedProfilePhoto ? 'unverified_member_profiles.photo_media_id as unverified_photo_media_id' : DB::raw('null as unverified_photo_media_id'),
                ]);
        } elseif ($hasUnverifiedProfiles) {
            $query->leftJoin('unverified_member_profiles', 'unverified_member_profiles.user_id', '=', 'users.id')
                ->addSelect([
                    'unverified_member_profiles.field_of_study as unverified_field_of_study',
                    'unverified_member_profiles.expertise_tags as unverified_expertise_tags',
                    'unverified_member_profiles.experience_years as unverified_experience_years',
                    $hasUnverifiedProfilePhoto ? 'unverified_member_profiles.photo_media_id as unverified_photo_media_id' : DB::raw('null as unverified_photo_media_id'),
                ]);
        } else {
            $query->addSelect([
                DB::raw('null as unverified_field_of_study'),
                DB::raw('null as unverified_expertise_tags'),
                DB::raw('null as unverified_experience_years'),
                DB::raw('null as unverified_photo_media_id'),
            ]);
        }

        if ($hasPartnerProfiles) {
            $query->leftJoin('partner_profiles', 'partner_profiles.user_id', '=', 'users.id')
                ->addSelect([
                    'partner_profiles.company_name as partner_company_name',
                    'partner_profiles.active_partner_subscription_id as partner_active_subscription_id',
                    'partner_profiles.subscription_expires_at as partner_subscription_expires_at',
                    'partner_profiles.contact_email as partner_contact_email',
                    'partner_profiles.country as partner_country',
                    'partner_profiles.website as partner_website',
                    'partner_profiles.subscription_status as partner_subscription_status',
                    'partner_profiles.keywords as partner_keywords',
                    'partner_profiles.founded_year as partner_founded_year',
                    'partner_profiles.approval_status as partner_approval_status',
                ]);

            if ($hasPlantTypes) {
                $query->leftJoin('plant_types as partner_plant_types', 'partner_plant_types.id', '=', 'partner_profiles.plant_type_id')
                    ->addSelect('partner_plant_types.name as partner_plant_type_name');
            } else {
                $query->addSelect(DB::raw('null as partner_plant_type_name'));
            }
        } else {
            $query->addSelect([
                DB::raw('null as partner_company_name'),
                DB::raw('null as partner_active_subscription_id'),
                DB::raw('null as partner_subscription_expires_at'),
                DB::raw('null as partner_contact_email'),
                DB::raw('null as partner_country'),
                DB::raw('null as partner_website'),
                DB::raw('null as partner_subscription_status'),
                DB::raw('null as partner_keywords'),
                DB::raw('null as partner_founded_year'),
                DB::raw('null as partner_approval_status'),
                DB::raw('null as partner_plant_type_name'),
            ]);
        }

        if ($hasEngineerProfiles && $this->hasEngineerProfilePlantTypePivot()) {
            $query
                ->selectSub(
                    $this->plantTypeNamesSubquery('engineer_profile_plant_type', 'engineer_profile_id', 'engineer_profiles.id'),
                    'engineer_plant_type_names'
                )
                ->selectSub(
                    $this->plantTypeNamesSubquery('engineer_profile_plant_type', 'engineer_profile_id', 'engineer_profiles.id'),
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
        return $this->hasEngineerProfilePlantTypePivot()
            && Schema::hasTable('unverified_member_profile_plant_type');
    }


    private function hasEngineerProfilePlantTypePivot(): bool
    {
        return Schema::hasTable('engineer_profile_plant_type')
            && Schema::hasTable('plant_types');
    }

    /**
     * @param array<string, string> $filters
     */
    private function applyManagementFilters(Builder $query, string $memberView, array $filters): void
    {
        if ($memberView === 'engineers') {
            if (($filters['account_type'] ?? 'all') === 'registered') {
                $query->where('users.role', 'unverified_member');
            } elseif (($filters['account_type'] ?? 'all') === 'professional') {
                $query->where('users.role', 'professional');
            }
        }

        if (($filters['status'] ?? 'all') !== 'all') {
            if ($filters['status'] === 'pending') {
                $this->scopePending($query, $memberView);
            } else {
                $query->where('users.status', $filters['status']);
            }
        }
    }

    /**
     * @param array<int|string> $plantTypeIds
     */
    private function syncEngineerPlantTypes(int $profileId, array $plantTypeIds, int|string|null $primaryPlantTypeId = null): void
    {
        if ($profileId <= 0 || ! Schema::hasTable('engineer_profile_plant_type')) {
            return;
        }

        $selected = collect($plantTypeIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($selected->isEmpty()) {
            DB::table('engineer_profile_plant_type')->where('engineer_profile_id', $profileId)->delete();
            return;
        }

        $primary = (int) ($primaryPlantTypeId ?: $selected->first());
        if (! $selected->contains($primary)) {
            $primary = (int) $selected->first();
        }

        DB::table('engineer_profile_plant_type')->where('engineer_profile_id', $profileId)->delete();
        $selected->each(function (int $plantTypeId, int $index) use ($profileId, $primary): void {
            DB::table('engineer_profile_plant_type')->insert([
                'engineer_profile_id' => $profileId,
                'plant_type_id' => $plantTypeId,
                'is_primary' => $plantTypeId === $primary,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * @return array<string, array<int, array{id:int,name:string}>>
     */
    private function knowledgeDomainsByPlantType(): array
    {
        if (! Schema::hasTable('knowledge_domains')) {
            return [];
        }

        return DB::table('knowledge_domains')
            ->where('is_active', true)
            ->whereNotNull('plant_type_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'plant_type_id'])
            ->groupBy(fn ($domain) => (string) $domain->plant_type_id)
            ->map(fn ($domains) => $domains->map(fn ($domain) => ['id' => (int) $domain->id, 'name' => (string) $domain->name])->values()->all())
            ->all();
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
            default => $this->hasEngineerProfilePlantTypePivot()
                ? $query->whereExists(function ($query) use ($plantTypeId): void {
                    $query->selectRaw('1')
                        ->from('engineer_profile_plant_type')
                        ->whereColumn('engineer_profile_plant_type.engineer_profile_id', 'engineer_profiles.id')
                        ->where('engineer_profile_plant_type.plant_type_id', $plantTypeId);
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
            default => 'administrators',
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
        if ($user->role === 'partner' && Schema::hasTable('partner_profiles')) {
            $relations = [];
            if (Schema::hasTable('media_files')) {
                $relations[] = 'logoMedia';
            }
            if (Schema::hasTable('plant_types')) {
                $relations[] = 'plantType';
            }
            if (Schema::hasTable('partner_subscriptions') && Schema::hasTable('subscription_tiers')) {
                $relations[] = 'activePartnerSubscription.tier';
            }
            if (Schema::hasTable('partner_profile_plant_type') && Schema::hasTable('plant_types')) {
                $relations[] = 'plantTypes';
            }

            $profile = PartnerProfile::query()
                ->with($relations)
                ->where('user_id', $user->id)
                ->first();

            if ($profile) {
                $activeSubscription = $profile->activePartnerSubscription;
                if (! $activeSubscription && Schema::hasTable('partner_subscriptions')) {
                    $activeSubscription = PartnerSubscription::query()
                        ->with('tier')
                        ->active()
                        ->where('user_id', $user->id)
                        ->latest('starts_at')
                        ->latest('id')
                        ->first();
                }

                $profile->setAttribute('active_subscription', $activeSubscription);
                $profile->setAttribute('partner_tier', $activeSubscription?->tier?->display_name ?? ($profile->partner_tier ?? 'No active tier'));
            }

            return $profile;
        }

        return match ($user->role) {
            'professional' => Schema::hasTable('engineer_profiles') ? DB::table('engineer_profiles')->where('user_id', $user->id)->first() : null,
            'unverified_member' => Schema::hasTable('engineer_profiles')
                ? DB::table('engineer_profiles')->where('user_id', $user->id)->first()
                : (Schema::hasTable('unverified_member_profiles') ? DB::table('unverified_member_profiles')->where('user_id', $user->id)->first() : null),
            default => null,
        };
    }

    private function securityDetail(User $user): array
    {
        return [
            'active_sessions' => Schema::hasTable('sessions') ? $user->sessions()->count() : 0,
            'social_accounts' => Schema::hasTable('social_accounts') ? $user->socialAccounts()->count() : 0,
            'latest_ip' => 'Unknown',
        ];
    }

    private function activityDetail(User $user): array
    {
        $feed = Schema::hasTable('user_activity_feed')
            ? $user->activityFeed()->latest()->take(5)->get()
            : collect();

        $latestVerification = Schema::hasTable('verification_requests')
            ? $user->verificationRequests()->latest('id')->first()
            : null;

        return [
            'feed' => $feed,
            'feed_count' => $feed->count(),
            'verification_requests' => Schema::hasTable('verification_requests') ? $user->verificationRequests()->count() : 0,
            'pending_verifications' => Schema::hasTable('verification_requests') ? $user->verificationRequests()->where('status', 'pending')->count() : 0,
            'latest_verification' => $latestVerification,
        ];
    }

    private function metaDetail(User $user): array
    {
        if (! Schema::hasTable('user_metas')) {
            return [];
        }

        return $user->metas()->pluck('value', 'key')->all();
    }



    private function partnerLogoUrl(?object $profile): ?string
    {
        return $this->mediaUrl((int) ($profile->logo_media_id ?? 0));
    }

    private function profilePhotoUrl(?object $profile): ?string
    {
        return $this->mediaUrl((int) ($profile->photo_media_id ?? 0));
    }

    private function mediaUrl(int $mediaId): ?string
    {
        if ($mediaId <= 0 || ! Schema::hasTable('media_files')) {
            return null;
        }

        $media = MediaFile::query()->find($mediaId);
        if (! $media || ! $media->path) {
            return null;
        }

        try {
            return Storage::disk($media->disk ?: 'public')->url($media->path);
        } catch (\Throwable) {
            return null;
        }
    }

    private function storeEngineerProfilePhoto(UploadedFile $file, ?int $uploaderId): MediaFile
    {
        $path = $file->store('profile-photos', 'public');

        return MediaFile::create([
            'uploader_id' => $uploaderId,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'upload_context' => 'profile_photo',
            'file_category' => 'image',
            'sort_order' => 0,
            'is_watermarked' => false,
            'processing_status' => 'processed',
            'is_orphan' => true,
        ]);
    }

    private function bindEngineerProfilePhoto(User $user, string $role, int $engineerProfileId, MediaFile $media): void
    {
        [$table, $profileId, $attachableType] = $this->engineerPhotoTarget($user, $role, $engineerProfileId);
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'photo_media_id') || $profileId <= 0) {
            return;
        }

        DB::table($table)->where('id', $profileId)->update([
            'photo_media_id' => $media->id,
            'updated_at' => now(),
        ]);

        $media->forceFill([
            'attachable_type' => $attachableType,
            'attachable_id' => $profileId,
            'is_orphan' => false,
        ])->save();
    }

    /**
     * @return array{0:string,1:int,2:string}
     */
    private function engineerPhotoTarget(User $user, string $role, int $engineerProfileId): array
    {
        if ($role === 'unverified_member' && Schema::hasTable('unverified_member_profiles') && Schema::hasColumn('unverified_member_profiles', 'photo_media_id')) {
            $profileId = (int) DB::table('unverified_member_profiles')->where('user_id', $user->id)->value('id');
            if ($profileId > 0) {
                return ['unverified_member_profiles', $profileId, 'unverified_member_profiles'];
            }
        }

        return ['engineer_profiles', $engineerProfileId, EngineerProfile::class];
    }

    private function profilePlantFocus(User $user, ?object $profile): string
    {
        if (! $profile) {
            return 'No plant type';
        }

        if ($user->role === 'partner') {
            if ($profile instanceof PartnerProfile) {
                $plantTypes = $profile->relationLoaded('plantTypes') ? $profile->plantTypes->pluck('name') : collect();
                if ($plantTypes->isNotEmpty()) {
                    return $plantTypes->implode(', ');
                }

                if ($profile->relationLoaded('plantType') && $profile->plantType) {
                    return $profile->plantType->name;
                }
            }

            return $profile->plant_type_name ?? 'No plant type';
        }

        $pivotLabel = $this->engineerPlantTypeNames((int) ($profile->id ?? 0));

        return $pivotLabel
            ?: ($profile->plant_name ?? null)
            ?: $this->jsonLabel($profile->industry_specialization ?? null)
            ?: $this->jsonLabel($profile->expertise_tags ?? null)
            ?: ($profile->field_of_study ?? null)
            ?: 'No plant type';
    }

    private function engineerPlantTypeNames(int $profileId): ?string
    {
        if ($profileId <= 0 || ! Schema::hasTable('engineer_profile_plant_type') || ! Schema::hasTable('plant_types')) {
            return null;
        }

        $names = DB::table('engineer_profile_plant_type')
            ->join('plant_types', 'plant_types.id', '=', 'engineer_profile_plant_type.plant_type_id')
            ->where('engineer_profile_plant_type.engineer_profile_id', $profileId)
            ->orderByDesc('engineer_profile_plant_type.is_primary')
            ->orderBy('engineer_profile_plant_type.sort_order')
            ->orderBy('plant_types.name')
            ->pluck('plant_types.name')
            ->filter()
            ->values();

        return $names->isNotEmpty() ? $names->implode(', ') : null;
    }

    /**
     * @return array<int, int>
     */
    private function engineerProfilePlantTypeIds(User $user, ?object $profile): array
    {
        if (! $profile || ! Schema::hasTable('engineer_profile_plant_type')) {
            return [];
        }

        return DB::table('engineer_profile_plant_type')
            ->where('engineer_profile_id', (int) $profile->id)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->pluck('plant_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();
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

    private function subscriptionStartDate(?string $submittedStartDate, bool $activateAccount): ?Carbon
    {
        if ($submittedStartDate !== null && $submittedStartDate !== '') {
            return Carbon::parse($submittedStartDate)->startOfDay();
        }

        return $activateAccount ? now()->startOfDay() : null;
    }

    private function subscriptionEndDate(?string $submittedEndDate, ?Carbon $startsAt, SubscriptionTier $tier): ?Carbon
    {
        if ($submittedEndDate !== null && $submittedEndDate !== '') {
            return Carbon::parse($submittedEndDate)->endOfDay();
        }

        if (! $startsAt) {
            return null;
        }

        if ($tier->duration_days) {
            return $startsAt->copy()->addDays((int) $tier->duration_days)->endOfDay();
        }

        return match ($tier->billing_cycle) {
            'monthly' => $startsAt->copy()->addMonth()->endOfDay(),
            'yearly' => $startsAt->copy()->addYear()->endOfDay(),
            default => null,
        };
    }

    private function storePartnerLogo(?UploadedFile $file, ?int $uploaderId): ?MediaFile
    {
        if (! $file) {
            return null;
        }

        $path = $file->store('partner-logos', 'public');

        return MediaFile::create([
            'uploader_id' => $uploaderId,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'upload_context' => 'partner_asset',
            'file_category' => 'image',
            'sort_order' => 0,
            'is_watermarked' => false,
            'processing_status' => 'processed',
            'is_orphan' => true,
        ]);
    }

    private function keywordList(?string $keywords): array
    {
        $decoded = json_decode((string) $keywords, true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn (mixed $keyword) => trim((string) $keyword))
            ->filter()
            ->unique(fn (string $keyword) => mb_strtolower($keyword))
            ->values()
            ->all();
    }

    private function commaSeparatedList(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return json_encode([]);
        }

        $items = collect(explode(',', (string) $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->unique(fn (string $item) => mb_strtolower($item))
            ->values()
            ->all();

        return json_encode($items);
    }

    /**
     * @return array<int, string>
     */
    private function commaSeparatedArray(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(explode(',', (string) $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->unique(fn (string $item) => mb_strtolower($item))
            ->values()
            ->all();
    }

    private function uniqueUsername(string $email, string $fallback): string
    {
        $base = Str::slug(Str::before($email, '@') ?: $fallback, '_') ?: 'partner';
        $username = $base;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'_'.++$suffix;
        }

        return $username;
    }

    private function partnerTierStats(): array
    {
        return SubscriptionTier::query()
            ->active()
            ->withCount(['partnerSubscriptions as active_partners_count' => fn (Builder $query) => $query->active()])
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get()
            ->mapWithKeys(fn (SubscriptionTier $tier) => [(string) $tier->id => [
                'label' => $tier->display_name,
                'code' => $tier->code,
                'count' => $tier->active_partners_count,
            ]])
            ->all();
    }

    private function subscriptionTierOptions(): array
    {
        if (! Schema::hasTable('subscription_tiers')) {
            return [];
        }

        return SubscriptionTier::query()->active()->orderBy('sort_order')->orderBy('display_name')->pluck('display_name', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    private function plantTypeOptions(): array
    {
        if (! Schema::hasTable('plant_types')) {
            return [];
        }

        return PlantType::query()
            ->active()
            ->sorted()
            ->pluck('name', 'id')
            ->all();
    }
}








