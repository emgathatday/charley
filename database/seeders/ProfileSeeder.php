<?php

namespace Database\Seeders;

use App\Models\Connection;
use App\Models\EngineerProfile;
use App\Models\SearchIndexEntry;
use App\Models\User;
use App\Services\ProfileSearchIndexService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('users')
            || ! Schema::hasTable('engineer_profiles')
            || ! Schema::hasTable('connections')
            || ! Schema::hasTable('search_index_entries')) {
            return;
        }

        $professionalUsers = $this->professionalUsers();
        $unverifiedUsers = $this->unverifiedUsers();
        $partnerUser = $this->partnerUser();

        $this->removeExtraDemoEngineers($professionalUsers, $unverifiedUsers);

        $photoMediaId = $this->profileMediaId($professionalUsers[0], 'image', 'profile_photo', 'profiles/demo/avatar.jpg');
        $documentMediaId = $this->profileMediaId($professionalUsers[0], 'document', 'verification_document', 'profiles/demo/verification.pdf');
        $engineerProfiles = [];

        foreach ($professionalUsers as $index => $user) {
            $profile = EngineerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $this->professionalProfileData($index, $photoMediaId, $documentMediaId)
            );

            $this->syncPlantTypes($profile, $index);
            $engineerProfiles[] = $profile->fresh(['user', 'plantTypes']);
        }

        foreach ($unverifiedUsers as $index => $user) {
            $profile = EngineerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $this->unverifiedEngineerProfileData($index, $photoMediaId)
            );

            $this->syncPlantTypes($profile, $index + count($professionalUsers));
            $engineerProfiles[] = $profile->fresh(['user', 'plantTypes']);
        }

        $this->seedConnections($professionalUsers, $partnerUser);
        $this->seedSearchIndex($engineerProfiles);
    }

    private function professionalUsers(): array
    {
        $users = [
            ['username' => 'sample-professional', 'first_name' => 'Sample', 'last_name' => 'Professional', 'email' => 'professional@example.test'],
        ];

        return array_map(fn (array $attributes): User => $this->sampleUser($attributes, 'professional', true), $users);
    }

    private function unverifiedUsers(): array
    {
        $users = [
            ['username' => 'sample-member', 'first_name' => 'Sample', 'last_name' => 'Member', 'email' => 'member@example.test'],
        ];

        return array_map(fn (array $attributes): User => $this->sampleUser($attributes, 'unverified_member'), $users);
    }

    private function partnerUser(): User
    {
        return $this->sampleUser([
            'username' => 'sample_partner_finn',
            'first_name' => 'Finn',
            'last_name' => 'Partner',
            'email' => 'finn.partner@example.test',
        ], 'partner', true);
    }

    private function removeExtraDemoEngineers(array $professionalUsers, array $unverifiedUsers): void
    {
        $keepIds = collect([...$professionalUsers, ...$unverifiedUsers])
            ->pluck('id')
            ->all();
        $fallbackUploaderId = $professionalUsers[0]->id ?? $unverifiedUsers[0]->id ?? null;

        $extraIds = User::query()
            ->whereIn('role', ['professional', 'unverified_member'])
            ->where(function ($query): void {
                $query->where('email', 'like', 'sample\_%@example.test')
                    ->orWhereIn('email', [
                        'anna.process@example.test',
                        'ben.reliability@example.test',
                        'chloe.operations@example.test',
                        'dan.graduate@example.test',
                        'emma.intern@example.test',
                    ]);
            })
            ->whereNotIn('id', $keepIds)
            ->pluck('id');

        if ($extraIds->isEmpty()) {
            return;
        }

        if ($fallbackUploaderId !== null && Schema::hasTable('media_files')) {
            DB::table('media_files')
                ->whereIn('uploader_id', $extraIds)
                ->update(['uploader_id' => $fallbackUploaderId]);
        }

        User::query()
            ->whereIn('id', $extraIds)
            ->delete();
    }

    private function sampleUser(array $attributes, string $role, bool $verified = false): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $attributes['email']],
            [
                'username' => $attributes['username'],
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
                'password' => Hash::make('password'),
                'role' => $role,
                'status' => 'active',
                'is_verified' => $verified,
                'verified_at' => $verified ? now() : null,
                'verification_expires_at' => $role === 'professional' ? now()->addYear() : null,
            ]
        );

        $user->forceFill([
            'role' => $role,
            'status' => 'active',
            'is_verified' => $verified,
            'verified_at' => $verified ? ($user->verified_at ?? now()) : null,
            'verification_expires_at' => $role === 'professional' ? ($user->verification_expires_at ?? now()->addYear()) : null,
        ])->save();

        return $user;
    }

    private function profileMediaId(User $uploader, string $category, string $context, string $path): ?int
    {
        if (! Schema::hasTable('media_files')) {
            return null;
        }

        $attributes = [
            'uploader_id' => $uploader->id,
            'disk' => 's3',
            'original_name' => basename($path),
            'mime_type' => $category === 'image' ? 'image/jpeg' : 'application/pdf',
            'size' => $category === 'image' ? 128000 : 256000,
            'upload_context' => $context,
            'file_category' => $category,
            'sort_order' => 0,
            'is_watermarked' => false,
            'is_orphan' => false,
            'updated_at' => now(),
        ];

        $mediaId = DB::table('media_files')->where('path', $path)->value('id');

        if ($mediaId !== null) {
            DB::table('media_files')->where('id', $mediaId)->update($attributes);

            return (int) $mediaId;
        }

        return (int) DB::table('media_files')->insertGetId($attributes + [
            'path' => $path,
            'created_at' => now(),
        ]);
    }

    private function professionalProfileData(int $index, ?int $photoMediaId, ?int $documentMediaId): array
    {
        $profiles = [
            [
                'bio' => 'Process engineer focused on plant optimization, operator training, and reliability programs.',
                'current_company' => 'Charley Process Advisory',
                'position' => 'Senior Process Engineer',
                'plant_name' => 'North Loop Facility',
                'experience_years' => 12,
                'expertise_tags' => ['process safety', 'operations', 'commissioning'],
                'industry_specialization' => ['chemicals', 'utilities'],
                'searchable_keywords' => ['process optimization', 'operator training', 'root cause analysis'],
                'job_availability' => 'open_to_opportunities',
                'reputation_points' => 860,
            ],
            [
                'bio' => 'Reliability specialist supporting maintenance planning and asset integrity programs.',
                'current_company' => 'Charley Reliability Group',
                'position' => 'Reliability Lead',
                'plant_name' => 'West Maintenance Hub',
                'experience_years' => 16,
                'expertise_tags' => ['maintenance', 'reliability', 'asset integrity'],
                'industry_specialization' => ['power', 'utilities'],
                'searchable_keywords' => ['maintenance strategy', 'asset integrity', 'turnaround'],
                'job_availability' => 'not_looking',
                'reputation_points' => 1240,
            ],
            [
                'bio' => 'Operations mentor helping teams improve startup readiness and field execution.',
                'current_company' => 'Charley Operations Network',
                'position' => 'Operations Consultant',
                'plant_name' => 'East Operations Center',
                'experience_years' => 9,
                'expertise_tags' => ['operations', 'training', 'automation'],
                'industry_specialization' => ['fertilizer', 'chemicals'],
                'searchable_keywords' => ['startup readiness', 'shift handover', 'field execution'],
                'job_availability' => 'open',
                'reputation_points' => 640,
            ],
        ];

        $data = $profiles[$index % count($profiles)];

        return $data + $this->commonProfileData($photoMediaId) + [
            'current_institution' => null,
            'field_of_study' => null,
            'verification_intent' => false,
            'verification_document_media_id' => $documentMediaId,
            'verification_renewed_at' => now()->subDays(30 + $index),
            'renewal_reminder_sent_at' => null,
        ];
    }

    private function unverifiedEngineerProfileData(int $index, ?int $photoMediaId): array
    {
        $profiles = [
            ['bio' => 'Graduate engineer looking for plant operations learning opportunities.', 'current_institution' => 'Example Technical University', 'field_of_study' => 'Chemical Engineering', 'experience_years' => 1, 'verification_intent' => true],
            ['bio' => 'Engineering intern interested in maintenance, safety, and process improvement.', 'current_institution' => 'Example Industrial Internship Program', 'field_of_study' => 'Mechanical Engineering', 'experience_years' => 0, 'verification_intent' => false],
        ];

        $data = $profiles[$index % count($profiles)];

        return $data + $this->commonProfileData($photoMediaId) + [
            'current_company' => null,
            'position' => null,
            'plant_name' => null,
            'industry_specialization' => null,
            'expertise_tags' => ['training', 'plant operations', 'process safety'],
            'searchable_keywords' => ['graduate engineer', 'entry level', 'training'],
            'phone' => null,
            'linkedin_url' => 'https://example.test/profiles/unverified-'.$index,
            'reputation_points' => 0,
            'reputation_breakdown' => null,
            'verification_document_media_id' => null,
            'verification_renewed_at' => null,
            'renewal_reminder_sent_at' => null,
        ];
    }

    private function commonProfileData(?int $photoMediaId): array
    {
        return [
            'photo_media_id' => $photoMediaId,
            'education' => 'Engineering degree with continuing professional development.',
            'references' => [['name' => 'Sample Reference', 'company' => 'Example Industrial Group', 'role' => 'Operations Manager']],
            'ai_usage_count' => 0,
            'is_discoverable' => true,
            'privacy_settings' => ['show_email' => 'connections_only', 'show_phone' => 'none', 'show_activity_feed' => true],
            'notification_preferences' => ['connection_requests' => true, 'directory_mentions' => true, 'verification_reminders' => true],
            'linkedin_url' => 'https://example.test/profiles/professional',
            'job_availability' => 'open',
        ];
    }

    private function syncPlantTypes(EngineerProfile $profile, int $offset): void
    {
        if (! Schema::hasTable('plant_types') || ! Schema::hasTable('engineer_profile_plant_type')) {
            return;
        }

        $plantTypeIds = DB::table('plant_types')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('id')
            ->values();

        if ($plantTypeIds->isEmpty()) {
            return;
        }

        $primaryId = (int) $plantTypeIds[$offset % $plantTypeIds->count()];
        $secondaryId = $plantTypeIds->count() > 1 ? (int) $plantTypeIds[($offset + 1) % $plantTypeIds->count()] : null;
        $sync = [$primaryId => ['is_primary' => true, 'sort_order' => 0]];

        if ($secondaryId !== null && $secondaryId !== $primaryId) {
            $sync[$secondaryId] = ['is_primary' => false, 'sort_order' => 1];
        }

        $profile->plantTypes()->sync($sync);
    }

    private function seedConnections(array $professionalUsers, User $partnerUser): void
    {
        if (count($professionalUsers) < 3) {
            return;
        }

        $connections = [
            ['requester_id' => $professionalUsers[0]->id, 'receiver_id' => $professionalUsers[1]->id, 'status' => 'accepted', 'initiated_context' => 'engineer_to_engineer', 'accepted_at' => now()->subDays(10)],
            ['requester_id' => $partnerUser->id, 'receiver_id' => $professionalUsers[2]->id, 'status' => 'pending', 'initiated_context' => 'partner_to_engineer'],
        ];

        foreach ($connections as $connection) {
            Connection::query()->updateOrCreate(
                ['requester_id' => $connection['requester_id'], 'receiver_id' => $connection['receiver_id']],
                [
                    'status' => $connection['status'],
                    'initiated_context' => $connection['initiated_context'],
                    'declined_at' => $connection['declined_at'] ?? null,
                    'accepted_at' => $connection['accepted_at'] ?? null,
                    'blocked_at' => $connection['blocked_at'] ?? null,
                    'blocked_by' => $connection['blocked_by'] ?? null,
                ]
            );
        }
    }

    private function seedSearchIndex(array $engineerProfiles): void
    {
        $service = app(ProfileSearchIndexService::class);

        foreach ($engineerProfiles as $profile) {
            if ($profile instanceof EngineerProfile) {
                $service->refresh($profile->loadMissing(['user', 'plantTypes']));
            }
        }

        SearchIndexEntry::query()
            ->where('indexable_type', 'like', '%UnverifiedMemberProfile')
            ->delete();
    }
}
