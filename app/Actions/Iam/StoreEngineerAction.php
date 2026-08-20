<?php

namespace App\Actions\Iam;

use App\Models\EngineerProfile;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\DataTransferObjects\Iam\EngineerAccountResult;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEngineerAction
{
    public function execute(array $data): EngineerAccountResult
    {
        return DB::transaction(function () use ($data): EngineerAccountResult {
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
                'verification_intent' => (bool) ($data['verification_intent'] ?? false),
            ]);

            $this->syncEngineerPlantTypes((int) $profile->id, $data['plant_type_ids'] ?? [], $data['primary_plant_type_id'] ?? null);

            return new EngineerAccountResult($user);
        });
    }

    public function rules(): array
    {
        $plantTypeIds = $this->plantTypeIds();

        return [
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
        ];
    }

    private function plantTypeIds(): array
    {
        return PlantType::query()
            ->active()
            ->sorted()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function syncEngineerPlantTypes(int $profileId, array $plantTypeIds, mixed $primaryPlantTypeId): void
    {
        $selected = collect($plantTypeIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($selected->isEmpty()) {
            DB::table('engineer_profile_plant_type')->where('engineer_profile_id', $profileId)->delete();

            return;
        }

        $primary = (int) $primaryPlantTypeId;
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
        $base = Str::slug(Str::before($email, '@') ?: $fallback, '_') ?: 'engineer';
        $username = $base;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'_'.++$suffix;
        }

        return $username;
    }
}
