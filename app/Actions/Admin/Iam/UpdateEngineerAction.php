<?php

namespace App\Actions\Admin\Iam;

use App\Models\EngineerProfile;
use App\Models\MediaFile;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UpdateEngineerAction
{
    public function execute(Request $request, User $user): User
    {
        $data = $request->validate($this->rules($user));

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
                'expertise_tags' => $this->commaSeparatedArray($data['expertise_tags'] ?? null),
                'industry_specialization' => $this->commaSeparatedArray($data['industry_specialization'] ?? null),
                'searchable_keywords' => $this->commaSeparatedArray($data['searchable_keywords'] ?? null),
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
                $photoMedia = $this->storeProfilePhoto($photoFile, $request->user()?->id);
                $this->bindProfilePhoto($user, $role, $profileId, $photoMedia);
            }

            $this->syncPlantTypes($profileId, $data['plant_type_ids'] ?? []);
        });

        return $user;
    }

    private function rules(User $user): array
    {
        $plantTypeIds = $this->plantTypeIds();
        $plantTypeRules = $plantTypeIds === [] ? ['nullable', 'array'] : ['required', 'array', 'min:1'];

        return [
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
        ];
    }

    private function plantTypeIds(): array
    {
        if (! Schema::hasTable('plant_types')) {
            return [];
        }

        return PlantType::query()->active()->sorted()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function storeProfilePhoto(UploadedFile $file, ?int $uploaderId): MediaFile
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

    private function bindProfilePhoto(User $user, string $role, int $engineerProfileId, MediaFile $media): void
    {
        [$table, $profileId, $attachableType] = $this->photoTarget($user, $role, $engineerProfileId);
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

    private function photoTarget(User $user, string $role, int $engineerProfileId): array
    {
        if ($role === 'unverified_member' && Schema::hasTable('unverified_member_profiles') && Schema::hasColumn('unverified_member_profiles', 'photo_media_id')) {
            $profileId = (int) DB::table('unverified_member_profiles')->where('user_id', $user->id)->value('id');
            if ($profileId > 0) {
                return ['unverified_member_profiles', $profileId, 'unverified_member_profiles'];
            }
        }

        return ['engineer_profiles', $engineerProfileId, EngineerProfile::class];
    }

    private function syncPlantTypes(int $profileId, array $plantTypeIds): void
    {
        if (! Schema::hasTable('engineer_profile_plant_type')) {
            return;
        }

        $selectedPlantTypeIds = collect($plantTypeIds)->map(fn ($id) => (int) $id)->unique()->values();
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
}
