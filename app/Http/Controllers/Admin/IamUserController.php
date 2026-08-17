<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Iam\StoreEngineerAction;
use App\Actions\Admin\Iam\StorePartnerAction;
use App\Actions\Admin\Iam\UpdateEngineerAction;
use App\Actions\Admin\Iam\UpdatePartnerAction;
use App\Http\Controllers\Controller;
use App\Models\EngineerProfile;
use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Services\Admin\AdministratorManagementViewDataService;
use App\Services\Admin\CreateEngineerViewDataService;
use App\Services\Admin\CreatePartnerViewDataService;
use App\Services\Admin\EditEngineerViewDataService;
use App\Services\Admin\EditPartnerViewDataService;
use App\Services\Admin\EngineerManagementViewDataService;
use App\Services\Admin\PartnerManagementViewDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IamUserController extends Controller
{
    public function index(Request $request, AdministratorManagementViewDataService $viewData): View
    {
        return view('iam.users', $viewData->data($request->query()));
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

    public function engineers(Request $request, EngineerManagementViewDataService $viewData): View
    {
        return view('iam.users.engineers', $viewData->data($request->query()));
    }

    public function partners(Request $request, PartnerManagementViewDataService $viewData): View
    {
        return view('iam.users.partners', $viewData->data($request->query()));
    }

    public function createPartner(CreatePartnerViewDataService $viewData): View
    {
        return view('iam.users.create-partner', $viewData->data(old('keywords')));
    }

    public function storePartner(Request $request, StorePartnerAction $storePartner): RedirectResponse
    {
        [$user, $subscription] = $storePartner->execute($request);

        return redirect()->route('admin.dashboard.iam.users.show', $user)->with('status', 'Partner account created.')->with('subscription_id', $subscription->id);
    }

    public function createEngineer(CreateEngineerViewDataService $viewData): View
    {
        return view('iam.users.create-engineer', $viewData->data());
    }

    public function createAdmin(): View
    {
        return view('iam.users.create-admin');
    }

    public function editEngineer(User $user, EditEngineerViewDataService $viewData): View
    {
        if (! in_array($user->role, ['professional', 'unverified_member'], true)) {
            abort(404);
        }

        return view('iam.users.edit-engineer', $viewData->data($user));
    }

    public function editPartner(User $user, EditPartnerViewDataService $viewData): View
    {
        if ($user->role !== 'partner') {
            abort(404);
        }

        return view('iam.users.edit-partner', $viewData->data($user));
    }

    public function adminProfile(Request $request): View
    {
        $admin = $request->user();
        $currentSessionId = $request->session()->getId();
        $sessions = Schema::hasTable('sessions')
            ? $admin->sessions()->orderByDesc('last_activity')->get()->each(function ($session) use ($currentSessionId): void {
                $session->is_current = hash_equals((string) $session->id, (string) $currentSessionId);
            })
            : collect();
        $latestSession = $sessions->firstWhere('is_current', true) ?? $sessions->first();

        return view('iam.users.admin-profile', [
            'admin' => $admin,
            'displayName' => $this->displayName($admin),
            'initials' => 'AD',
            'profileTitle' => 'Platform Administrator',
            'organisation' => 'Charley Platform',
            'timezone' => config('app.timezone'),
            'sessions' => $sessions,
            'latestSession' => $latestSession,
        ]);
    }

    public function revokeAdminProfileSession(Request $request, string $session): RedirectResponse
    {
        if (Schema::hasTable('sessions')) {
            $preservedSessionId = $this->adminProfileSessionIdToPreserve($request);

            if (! $preservedSessionId || ! hash_equals((string) $preservedSessionId, $session)) {
                $deleted = $request->user()->sessions()
                    ->whereKey($session)
                    ->delete();

                if ($deleted > 0) {
                    $this->rotateAdminRememberToken($request);
                }
            }
        }

        return redirect()
            ->route('admin.dashboard.iam.users.admin-profile', ['section' => 'sessions'])
            ->with('status', 'Session revoked.');
    }

    public function revokeOtherAdminProfileSessions(Request $request): RedirectResponse
    {
        if (Schema::hasTable('sessions')) {
            $preservedSessionId = $this->adminProfileSessionIdToPreserve($request);

            $deleted = $request->user()->sessions()
                ->when($preservedSessionId, fn ($query) => $query->whereKeyNot($preservedSessionId))
                ->delete();

            if ($deleted > 0) {
                $this->rotateAdminRememberToken($request);
            }
        }

        return redirect()
            ->route('admin.dashboard.iam.users.admin-profile', ['section' => 'sessions'])
            ->with('status', 'Other sessions revoked.');
    }

    private function adminProfileSessionIdToPreserve(Request $request): ?string
    {
        $currentSessionId = (string) $request->session()->getId();
        if ($request->user()->sessions()->whereKey($currentSessionId)->exists()) {
            return $currentSessionId;
        }

        return $request->user()->sessions()
            ->orderByDesc('last_activity')
            ->value('id');
    }

    private function rotateAdminRememberToken(Request $request): void
    {
        $request->user()->forceFill([
            'remember_token' => Str::random(60),
        ])->save();
    }

    public function storeEngineer(Request $request, StoreEngineerAction $storeEngineer): RedirectResponse
    {
        $user = $storeEngineer->execute($request);

        return redirect()->route('admin.dashboard.iam.users.show', $user)->with('status', 'Engineer account created.');
    }

    public function updateEngineer(Request $request, User $user, UpdateEngineerAction $updateEngineer): RedirectResponse
    {
        if (! in_array($user->role, ['professional', 'unverified_member'], true)) {
            abort(404);
        }

        $updateEngineer->execute($request, $user);

        return redirect()->route('admin.dashboard.iam.users.show', $user)->with('status', 'Engineer profile updated.');
    }

    public function updatePartner(Request $request, User $user, UpdatePartnerAction $updatePartner): RedirectResponse
    {
        if ($user->role !== 'partner') {
            abort(404);
        }

        $updatePartner->execute($request, $user);

        return redirect()->route('admin.dashboard.iam.users.show', $user)->with('status', 'Partner profile updated.');
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        return redirect()->route('admin.dashboard.iam.users');
    }

    private function displayName(User $user): string
    {
        $name = trim(implode(' ', array_filter([$user->first_name, $user->last_name])));

        return $name !== '' ? $name : ($user->username ?: $user->email);
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

}
