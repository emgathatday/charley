<?php

namespace App\Services\Admin;

use App\Models\MediaFile;
use App\Models\PlantType;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class EditEngineerViewDataService
{
    public function data(User $user): array
    {
        $profile = $this->profileFor($user);
        $displayName = $this->displayName($user);
        $experienceYears = $profile->experience_years ?? null;
        $rank = $this->rankFor($experienceYears);
        $expertiseTags = $this->jsonList($profile->expertise_tags ?? null);
        $industrySpecialization = $this->jsonList($profile->industry_specialization ?? null);
        $searchableKeywords = $this->jsonList($profile->searchable_keywords ?? null);
        $domainContext = $this->domainContext($user, $industrySpecialization, $rank['ceiling']);
        $latestVerificationRequest = $this->latestVerificationRequest($user);
        $isProfessional = old('account_type', $user->role === 'professional' ? 'professional' : 'member') === 'professional';

        return [
            'user' => $user,
            'profile' => $profile,
            'profilePhotoUrl' => $this->profilePhotoUrl($profile),
            'plantTypeOptions' => $this->plantTypeOptions(),
            'selectedPlantTypes' => collect(old('plant_type_ids', $this->engineerProfilePlantTypeIds($profile)))->map(fn ($id) => (string) $id)->all(),
            'latestVerificationRequest' => $latestVerificationRequest,
            'isProfessional' => $isProfessional,
            'displayName' => $displayName,
            'initials' => $this->initials($displayName),
            'company' => $profile->current_company ?? $profile->current_institution ?? '',
            'position' => $profile->position ?? $profile->field_of_study ?? '',
            'experienceYears' => $experienceYears,
            'expertiseTags' => $expertiseTags,
            'industrySpecialization' => $industrySpecialization,
            'searchableKeywords' => $searchableKeywords,
            'expertiseTagItems' => $this->splitList($expertiseTags),
            'keywordItems' => $this->splitList($searchableKeywords),
            'rankLabel' => $rank['label'],
            'rankCeiling' => $rank['ceiling'],
            'domainPayload' => $domainContext['payload'],
            'topAreaRows' => $domainContext['top_rows'],
            'verificationMethod' => $latestVerificationRequest->verification_method ?? null,
            'editNavGroups' => $this->editNavGroups(),
            'basicFieldRows' => $this->basicFieldRows($user, $profile, $displayName),
            'professionalFieldRows' => $this->professionalFieldRows($profile),
            'accountSelectRows' => $this->accountSelectRows($user, $isProfessional),
            'accountReadonlyRows' => $this->accountReadonlyRows($user),
            'rankOptions' => [
                'Registered Member' => '(Unverified)',
                'Industry Professional' => '0-7 yrs',
                'Experienced Professional' => '8-15 yrs',
                'Senior Industry Expert' => '15+ yrs',
            ],
        ];
    }

    private function profileFor(User $user): ?object
    {
        if (! Schema::hasTable('engineer_profiles')) {
            return null;
        }

        return DB::table('engineer_profiles')->where('user_id', $user->id)->first();
    }

    private function displayName(User $user): string
    {
        $name = trim(implode(' ', array_filter([$user->first_name, $user->last_name])));

        return $name !== '' ? $name : ($user->username ?: $user->email);
    }

    private function initials(string $displayName): string
    {
        return collect(explode(' ', trim($displayName)))
            ->filter()
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('') ?: 'U';
    }

    private function profilePhotoUrl(?object $profile): ?string
    {
        $mediaId = (int) ($profile->photo_media_id ?? 0);
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

    private function plantTypeOptions(): array
    {
        if (! Schema::hasTable('plant_types')) {
            return [];
        }

        return PlantType::query()->active()->sorted()->pluck('name', 'id')->all();
    }

    private function engineerProfilePlantTypeIds(?object $profile): array
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

    private function latestVerificationRequest(User $user): ?VerificationRequest
    {
        if (! Schema::hasTable('verification_requests')) {
            return null;
        }

        return VerificationRequest::query()->where('user_id', $user->id)->latest('id')->first();
    }

    private function jsonList(mixed $value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        if (is_array($value)) {
            return collect($value)->flatten()->filter()->implode(', ');
        }

        return (string) ($value ?? '');
    }

    private function splitList(string $value): Collection
    {
        return collect(explode(',', $value))->map(fn (string $item) => trim($item))->filter()->values();
    }

    private function rankFor(mixed $experienceYears): array
    {
        $experienceValue = is_numeric($experienceYears) ? (int) $experienceYears : null;

        if ($experienceValue === null) {
            return ['label' => 'Registered Member', 'ceiling' => 0];
        }

        if ($experienceValue >= 15) {
            return ['label' => 'Senior Industry Expert', 'ceiling' => 70];
        }

        if ($experienceValue >= 8) {
            return ['label' => 'Experienced Professional', 'ceiling' => 50];
        }

        return ['label' => 'Industry Professional', 'ceiling' => 30];
    }

    private function domainContext(User $user, string $industrySpecialization, int $rankCeiling): array
    {
        $knowledgeDomainOptions = Schema::hasTable('knowledge_domains')
            ? DB::table('knowledge_domains')->select('id', 'name', 'plant_type_id')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            : collect();
        $userDomainExpertise = Schema::hasTable('user_domain_expertise')
            ? DB::table('user_domain_expertise')->where('user_id', $user->id)->get()->keyBy('knowledge_domain_id')
            : collect();
        $passedDomainIds = Schema::hasTable('quiz_attempts')
            ? DB::table('quiz_attempts')->where('user_id', $user->id)->where('is_passed', true)->pluck('knowledge_domain_id')->map(fn ($id) => (string) $id)->all()
            : [];

        $payload = $knowledgeDomainOptions->map(function (object $domain) use ($userDomainExpertise, $passedDomainIds): array {
            $expertise = $userDomainExpertise->get($domain->id);

            return [
                'id' => (string) $domain->id,
                'name' => $domain->name,
                'plant_type_id' => is_null($domain->plant_type_id) ? null : (string) $domain->plant_type_id,
                'self_rated_percentage' => is_null($expertise?->self_rated_percentage) ? null : (int) $expertise->self_rated_percentage,
                'is_quiz_unlocked' => (bool) ($expertise?->is_quiz_unlocked ?? false),
                'quiz_passed' => in_array((string) $domain->id, $passedDomainIds, true),
            ];
        })->values();

        $topRows = $this->splitList($industrySpecialization)->take(5)->map(function (string $area) use ($knowledgeDomainOptions, $userDomainExpertise, $passedDomainIds, $rankCeiling): array {
            $domain = $knowledgeDomainOptions->first(fn (object $domain) => strcasecmp($domain->name, $area) === 0);
            $expertise = $domain ? $userDomainExpertise->get($domain->id) : null;
            $isUnlocked = (bool) ($expertise?->is_quiz_unlocked ?? false) || ($domain && in_array((string) $domain->id, $passedDomainIds, true));
            $rating = is_null($expertise?->self_rated_percentage) ? min($rankCeiling, 65) : (int) $expertise->self_rated_percentage;

            return [
                'name' => $area,
                'domain_id' => $domain ? (string) $domain->id : '',
                'plant_type_id' => $domain && ! is_null($domain->plant_type_id) ? (string) $domain->plant_type_id : '',
                'quiz_passed' => $domain && in_array((string) $domain->id, $passedDomainIds, true),
                'is_quiz_unlocked' => $isUnlocked,
                'value' => min($isUnlocked ? 100 : $rankCeiling, max(0, $rating)),
            ];
        })->values();

        if ($topRows->isEmpty()) {
            $topRows = collect([[
                'name' => '',
                'domain_id' => '',
                'plant_type_id' => '',
                'quiz_passed' => false,
                'is_quiz_unlocked' => false,
                'value' => min($rankCeiling, 20),
            ]]);
        }

        return ['payload' => $payload, 'top_rows' => $topRows];
    }

    private function editNavGroups(): array
    {
        return [
            'Sections' => [
                ['id' => 'sec-basic', 'icon' => 'users-3', 'label' => 'Basic Information', 'active' => true],
                ['id' => 'sec-professional', 'icon' => 'partners', 'label' => 'Professional Details'],
                ['id' => 'sec-expertise', 'icon' => 'expertise-plant-focus', 'label' => 'Expertise & Plant Focus', 'dot' => true],
                ['id' => 'sec-topexpertise', 'icon' => 'top-expertise-areas', 'label' => 'Top Expertise Areas'],
                ['id' => 'sec-account', 'icon' => 'shield', 'label' => 'Account & Role'],
                ['id' => 'sec-verification', 'icon' => 'verification-queue', 'label' => 'Verification Status'],
                ['id' => 'sec-privacy', 'icon' => 'lock', 'label' => 'Privacy & Visibility'],
            ],
            'Activity' => [
                ['id' => 'sec-audit', 'icon' => 'ai-usage', 'label' => 'Edit Audit Log'],
            ],
        ];
    }

    private function basicFieldRows(User $user, ?object $profile, string $displayName): array
    {
        $position = $profile->position ?? $profile->field_of_study ?? '';

        return [
            [
                ['component' => 'input', 'label' => 'First Name', 'name' => 'first_name', 'value' => old('first_name', $user->first_name), 'required' => true, 'error' => 'first_name'],
                ['component' => 'input', 'label' => 'Last Name', 'name' => 'last_name', 'value' => old('last_name', $user->last_name), 'error' => 'last_name'],
            ],
            [
                ['component' => 'input', 'label' => 'Display Name', 'value' => $displayName, 'disabled' => true],
                ['component' => 'input', 'label' => 'Job Title', 'name' => 'position', 'value' => old('position', $position), 'required' => true, 'error' => 'position'],
            ],
            [
                ['component' => 'input', 'type' => 'email', 'label' => 'Work Email', 'name' => 'email', 'value' => old('email', $user->email), 'required' => true, 'error' => 'email', 'hint' => 'Used for verification and system notifications.'],
                ['component' => 'input', 'type' => 'email', 'label' => 'Secondary / Personal Email', 'placeholder' => 'Optional'],
            ],
            [
                ['component' => 'input', 'type' => 'url', 'label' => 'LinkedIn Profile URL', 'name' => 'linkedin_url', 'value' => old('linkedin_url', $profile->linkedin_url ?? ''), 'placeholder' => 'https://linkedin.com/in/...', 'error' => 'linkedin_url'],
                ['component' => 'select', 'label' => 'Country / Region', 'options' => ['Netherlands' => 'Netherlands', 'Saudi Arabia' => 'Saudi Arabia', 'United States' => 'United States', 'India' => 'India', 'Other' => 'Other']],
            ],
        ];
    }

    private function professionalFieldRows(?object $profile): array
    {
        $company = $profile->current_company ?? $profile->current_institution ?? '';

        return [
            [
                ['component' => 'input', 'label' => 'Company / Employer', 'name' => 'current_company', 'value' => old('current_company', $company), 'required' => true, 'error' => 'current_company'],
                ['component' => 'input', 'type' => 'number', 'label' => 'Years of Experience', 'name' => 'experience_years', 'value' => old('experience_years', $profile->experience_years ?? null), 'required' => true, 'min' => 0, 'max' => 80, 'error' => 'experience_years'],
            ],
            [
                ['component' => 'select', 'label' => 'Job Availability Status', 'name' => 'job_availability', 'selected' => old('job_availability', $profile->job_availability ?? ''), 'options' => ['not_looking' => 'Not open to opportunities', 'open_to_opportunities' => 'Open to opportunities', 'open' => 'Actively looking']],
            ],
        ];
    }

    private function accountSelectRows(User $user, bool $isProfessional): array
    {
        return [
            ['label' => 'Account Type', 'name' => 'account_type', 'selected' => $isProfessional ? 'professional' : 'member', 'options' => ['member' => 'Unverified Member', 'professional' => 'Verified Professional'], 'required' => true, 'error' => 'account_type', 'hint' => 'Changing this affects library, AI, and messaging access.'],
            ['label' => 'Account Status', 'name' => 'status', 'selected' => old('status', $user->status), 'options' => ['active' => 'Active', 'suspended' => 'Suspended', 'frozen' => 'Frozen'], 'required' => true, 'error' => 'status'],
        ];
    }

    private function accountReadonlyRows(User $user): array
    {
        return [
            ['label' => 'Registration Date', 'value' => $user->created_at?->format('d M Y') ?? '-'],
            ['label' => 'Last Login', 'value' => $user->last_login_at?->format('d M Y H:i') ?? 'Never'],
            ['label' => 'User ID', 'value' => '#'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT)],
        ];
    }
}
