<?php

namespace Database\Seeders;

use App\Models\Connection;
use App\Models\EngineerProfile;
use App\Models\SearchIndexEntry;
use App\Models\UnverifiedMemberProfile;
use App\Models\User;
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
            || ! Schema::hasTable('unverified_member_profiles')
            || ! Schema::hasTable('connections')
            || ! Schema::hasTable('search_index_entries')) {
            return;
        }

        $professionalUsers = $this->professionalUsers();
        $unverifiedUsers = $this->unverifiedUsers();
        $partnerUser = $this->partnerUser();

        $photoMediaId = $this->firstMediaId('image');
        $documentMediaId = $this->firstMediaId('document');
        $engineerProfiles = [];

        foreach ($professionalUsers as $index => $user) {
            $engineerProfiles[] = EngineerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $this->engineerProfileData($index, $photoMediaId, $documentMediaId)
            );
        }

        foreach ($unverifiedUsers as $index => $user) {
            UnverifiedMemberProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $this->unverifiedProfileData($index, $photoMediaId)
            );
        }

        $this->seedConnections($professionalUsers, $partnerUser);
        $this->seedSearchIndex($engineerProfiles);
    }

    private function professionalUsers(): array
    {
        $users = [
            ['username' => 'sample_professional_anna', 'first_name' => 'Anna', 'last_name' => 'Process', 'email' => 'anna.process@example.test'],
            ['username' => 'sample_professional_ben', 'first_name' => 'Ben', 'last_name' => 'Reliability', 'email' => 'ben.reliability@example.test'],
            ['username' => 'sample_professional_chloe', 'first_name' => 'Chloe', 'last_name' => 'Operations', 'email' => 'chloe.operations@example.test'],
        ];

        return array_map(fn (array $attributes): User => $this->sampleUser($attributes, 'professional', true), $users);
    }

    private function unverifiedUsers(): array
    {
        $users = [
            ['username' => 'sample_unverified_dan', 'first_name' => 'Dan', 'last_name' => 'Graduate', 'email' => 'dan.graduate@example.test'],
            ['username' => 'sample_unverified_emma', 'first_name' => 'Emma', 'last_name' => 'Intern', 'email' => 'emma.intern@example.test'],
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

    private function sampleUser(array $attributes, string $role, bool $verified = false): User
    {
        return User::query()->firstOrCreate(
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
            ]
        );
    }

    private function firstMediaId(?string $category = null): ?int
    {
        if (! Schema::hasTable('media_files')) {
            return null;
        }

        $query = DB::table('media_files')->orderBy('id');

        if ($category !== null && Schema::hasColumn('media_files', 'file_category')) {
            $query->where('file_category', $category);
        }

        return $query->value('id');
    }

    private function engineerProfileData(int $index, ?int $photoMediaId, ?int $documentMediaId): array
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

        return $data + [
            'photo_media_id' => $photoMediaId,
            'education' => 'Engineering degree with continuing professional development.',
            'references' => [['name' => 'Sample Reference', 'company' => 'Example Industrial Group', 'role' => 'Operations Manager']],
            'phone' => null,
            'linkedin_url' => 'https://example.test/profiles/professional-'.$index,
            'reputation_breakdown' => [
                'answers' => (int) floor($data['reputation_points'] * 0.4),
                'contributions' => (int) floor($data['reputation_points'] * 0.35),
                'endorsements' => (int) floor($data['reputation_points'] * 0.25),
            ],
            'ai_usage_count' => 0,
            'is_discoverable' => true,
            'privacy_settings' => ['show_email' => 'connections_only', 'show_phone' => 'none', 'show_activity_feed' => true],
            'notification_preferences' => ['connection_requests' => true, 'directory_mentions' => true, 'verification_reminders' => true],
            'verification_document_media_id' => $documentMediaId,
            'verification_renewed_at' => now()->subDays(30 + $index),
            'renewal_reminder_sent_at' => null,
        ];
    }

    private function unverifiedProfileData(int $index, ?int $photoMediaId): array
    {
        $profiles = [
            ['bio' => 'Graduate engineer looking for plant operations learning opportunities.', 'current_institution' => 'Example Technical University', 'field_of_study' => 'Chemical Engineering', 'experience_years' => 1, 'verification_intent' => true],
            ['bio' => 'Engineering intern interested in maintenance, safety, and process improvement.', 'current_institution' => 'Example Industrial Internship Program', 'field_of_study' => 'Mechanical Engineering', 'experience_years' => 0, 'verification_intent' => false],
        ];

        $data = $profiles[$index % count($profiles)];

        return $data + [
            'photo_media_id' => $photoMediaId,
            'education' => 'Relevant engineering coursework and project experience.',
            'references' => [['name' => 'Sample Mentor', 'context' => 'Academic or internship reference']],
            'expertise_tags' => ['training', 'plant operations', 'process safety'],
            'searchable_keywords' => ['graduate engineer', 'entry level', 'training'],
            'is_discoverable' => true,
            'privacy_settings' => ['show_email' => 'connections_only', 'show_phone' => 'none', 'show_activity_feed' => true],
            'notification_preferences' => ['connection_requests' => true, 'directory_mentions' => true, 'verification_reminders' => true],
            'linkedin_url' => 'https://example.test/profiles/unverified-'.$index,
            'job_availability' => 'open',
        ];
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
        foreach ($engineerProfiles as $profile) {
            SearchIndexEntry::query()->updateOrCreate(
                ['indexable_type' => EngineerProfile::class, 'indexable_id' => $profile->id, 'search_context' => 'expert_directory'],
                [
                    'searchable_text' => implode(' ', array_filter([
                        $profile->position,
                        $profile->current_company,
                        $profile->plant_name,
                        implode(' ', $profile->expertise_tags ?? []),
                        implode(' ', $profile->searchable_keywords ?? []),
                    ])),
                    'structured_data' => [
                        'company' => $profile->current_company,
                        'position' => $profile->position,
                        'experience_years' => $profile->experience_years,
                        'expertise_tags' => $profile->expertise_tags,
                        'job_availability' => $profile->job_availability,
                        'reputation_points' => $profile->reputation_points,
                    ],
                    'is_discoverable' => $profile->is_discoverable,
                    'last_indexed_at' => now(),
                ]
            );
        }
    }
}

