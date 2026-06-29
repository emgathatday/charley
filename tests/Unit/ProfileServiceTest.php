<?php

namespace Tests\Unit;

use App\Models\EngineerProfile;
use App\Models\UnverifiedMemberProfile;
use App\Models\User;
use App\Services\ProfileSearchIndexService;
use App\Services\ProfileService;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProfileService($this->createMock(ProfileSearchIndexService::class));
    }

    public function test_can_view_profile_when_viewer_owns_profile(): void
    {
        $viewer = new User(['role' => 'professional']);
        $viewer->id = 10;

        $profile = new EngineerProfile(['is_discoverable' => false]);
        $profile->user_id = 10;

        $this->assertTrue($this->service->canViewProfile($viewer, $profile), 'Profile owners should always view their own profiles.');
    }

    public function test_can_view_profile_when_viewer_is_admin(): void
    {
        $viewer = new User(['role' => 'admin']);
        $viewer->id = 20;

        $profile = new UnverifiedMemberProfile(['is_discoverable' => false]);
        $profile->user_id = 10;

        $this->assertTrue($this->service->canViewProfile($viewer, $profile), 'Admins should be able to view hidden profiles.');
    }

    public function test_can_view_profile_returns_false_when_profile_is_hidden(): void
    {
        $viewer = new User(['role' => 'professional']);
        $viewer->id = 20;

        $profile = new EngineerProfile(['is_discoverable' => false]);
        $profile->user_id = 10;

        $this->assertFalse($this->service->canViewProfile($viewer, $profile), 'Hidden profiles should not be visible to unrelated users.');
    }

    public function test_can_view_profile_returns_false_when_privacy_hides_activity_feed(): void
    {
        $viewer = new User(['role' => 'professional']);
        $viewer->id = 20;

        $profile = new EngineerProfile([
            'is_discoverable' => true,
            'privacy_settings' => ['show_activity_feed' => false],
        ]);
        $profile->user_id = 10;

        $this->assertFalse($this->service->canViewProfile($viewer, $profile), 'Privacy settings should be able to hide otherwise discoverable profiles.');
    }

    public function test_can_view_profile_allows_discoverable_profile_when_privacy_is_null(): void
    {
        $viewer = new User(['role' => 'professional']);
        $viewer->id = 20;

        $profile = new UnverifiedMemberProfile([
            'is_discoverable' => true,
            'privacy_settings' => null,
        ]);
        $profile->user_id = 10;

        $this->assertTrue($this->service->canViewProfile($viewer, $profile), 'Null privacy settings should use the public default.');
    }

    public function test_can_view_profile_rejects_unsupported_profile_type(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported profile type.');

        $viewer = new User(['role' => 'professional']);
        $viewer->id = 20;

        $this->service->canViewProfile($viewer, new class extends Model {
        });
    }

    public function test_upsert_engineer_profile_rejects_inactive_user_before_database_work(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only active users can manage profiles.');

        $this->service->upsertEngineerProfile(new User([
            'role' => 'professional',
            'status' => 'frozen',
        ]), []);
    }

    public function test_upsert_unverified_profile_rejects_wrong_role_before_database_work(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only unverified members can have unverified member profiles.');

        $this->service->upsertUnverifiedMemberProfile(new User([
            'role' => 'professional',
            'status' => 'active',
        ]), []);
    }
}
