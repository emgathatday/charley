<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\PartnerMember;
use App\Models\PartnerPresentation;
use App\Models\PartnerProduct;
use App\Models\PartnerProfile;
use App\Models\PlantType;
use App\Models\User;
use Database\Factories\PartnerMemberFactory;
use Database\Factories\PartnerPresentationFactory;
use Database\Factories\PartnerProductFactory;
use Database\Factories\PartnerProfileFactory;
use Database\Seeders\PartnerProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerProfileFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_profile_schema_contains_expected_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('partner_profiles', [
            'id',
            'user_id',
            'company_name',
            'logo_media_id',
            'overview',
            'partner_tier',
            'plant_type_id',
            'keywords',
            'references',
            'contact_email',
            'phone',
            'address',
            'country',
            'website',
            'founded_year',
            'social_links',
            'layout_template',
            'feed_highlight_enabled',
            'subscription_status',
            'subscription_expires_at',
            'approval_status',
            'verified_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_nested_partner_schema_contains_expected_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('partner_products', [
            'id',
            'partner_id',
            'name',
            'category',
            'item_type',
            'description',
            'image_media_id',
            'datasheet_media_id',
            'keywords',
            'is_active',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('partner_presentations', [
            'id',
            'partner_id',
            'title',
            'slug',
            'description',
            'plant_type_id',
            'equipment_category',
            'page_count',
            'download_allowed',
            'view_count',
            'status',
            'approved_by',
            'approved_at',
            'rejection_reason',
            'is_ai_trainable',
            'file_media_id',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('partner_members', [
            'id',
            'partner_id',
            'user_id',
            'member_role',
            'joined_at',
            'status',
        ]));
    }

    public function test_partner_profile_seeder_is_idempotent_when_dependencies_exist(): void
    {
        $user = User::factory()->professional()->create();
        $plantType = $this->createPlantType();

        $seeder = new PartnerProfileSeeder();
        $seeder->run();
        $seeder->run();

        $this->assertSame(1, PartnerProfile::query()->count());
        $this->assertSame(1, PartnerProduct::query()->count());
        $this->assertSame(1, PartnerPresentation::query()->count());
        $this->assertSame(1, PartnerMember::query()->count());
        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $user->id,
            'plant_type_id' => $plantType->id,
            'approval_status' => 'approved',
        ]);
    }

    public function test_partner_profile_list_requires_authentication(): void
    {
        $this->getJson('/api/v1/partner-profiles')->assertUnauthorized();
    }

    public function test_non_admin_lists_only_approved_profiles_and_can_filter_by_plant_type(): void
    {
        $user = User::factory()->professional()->create();
        $plantType = $this->createPlantType(['name' => 'Ammonia', 'slug' => 'ammonia']);
        $otherPlantType = $this->createPlantType(['name' => 'Methanol', 'slug' => 'methanol']);
        $approved = PartnerProfileFactory::new()->approved()->create([
            'company_name' => 'Approved Partner',
            'plant_type_id' => $plantType->id,
        ]);
        PartnerProfileFactory::new()->create([
            'approval_status' => 'pending',
            'plant_type_id' => $plantType->id,
        ]);
        PartnerProfileFactory::new()->approved()->create(['plant_type_id' => $otherPlantType->id]);

        PartnerProductFactory::new()->create(['partner_id' => $approved->id]);
        PartnerPresentationFactory::new()->create(['partner_id' => $approved->id]);
        PartnerMemberFactory::new()->create(['partner_id' => $approved->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/partner-profiles?plant_type_id={$plantType->id}&search=Approved")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'user_id',
                        'company_name',
                        'plant_type',
                        'products_count',
                        'presentations_count',
                        'members_count',
                        'approval_status',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id)
            ->assertJsonPath('data.0.products_count', 1)
            ->assertJsonPath('data.0.presentations_count', 1)
            ->assertJsonPath('data.0.members_count', 1);
    }

    public function test_admin_can_filter_profiles_by_approval_status_and_tier(): void
    {
        $admin = User::factory()->admin()->create();
        PartnerProfileFactory::new()->approved()->create(['partner_tier' => 'gold']);
        $pending = PartnerProfileFactory::new()->create(['approval_status' => 'pending', 'partner_tier' => 'diamond']);

        $this->actingAs($admin)
            ->getJson('/api/v1/partner-profiles?approval_status=pending&partner_tier=diamond')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id);
    }

    public function test_non_admin_cannot_view_unapproved_profile(): void
    {
        $user = User::factory()->professional()->create();
        $profile = PartnerProfileFactory::new()->create(['approval_status' => 'pending']);

        $this->actingAs($user)
            ->getJson("/api/v1/partner-profiles/{$profile->id}")
            ->assertNotFound();
    }

    public function test_admin_can_create_update_delete_and_approve_profile_with_media_and_plant_type(): void
    {
        $admin = User::factory()->admin()->create();
        $profileUser = User::factory()->professional()->create();
        $plantType = $this->createPlantType();
        $logoMedia = $this->createMediaFile($admin);

        $createResponse = $this->actingAs($admin)
            ->postJson('/api/v1/partner-profiles', [
                'user_id' => $profileUser->id,
                'company_name' => 'Partner Co',
                'logo_media_id' => $logoMedia->id,
                'overview' => null,
                'partner_tier' => 'gold',
                'plant_type_id' => $plantType->id,
                'keywords' => ['technology'],
                'references' => [['project' => 'Reference Unit']],
                'contact_email' => 'partner@example.test',
                'website' => 'https://example.test',
                'founded_year' => 2000,
                'social_links' => ['linkedin' => 'https://example.test/company'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $profileUser->id)
            ->assertJsonPath('data.logo_media_id', $logoMedia->id)
            ->assertJsonPath('data.overview', null);

        $profileId = $createResponse->json('data.id');

        $this->actingAs($admin)
            ->putJson("/api/v1/partner-profiles/{$profileId}", [
                'company_name' => 'Partner Co Updated',
                'approval_status' => 'pending',
            ])
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Partner Co Updated');

        $this->actingAs($admin)
            ->postJson("/api/v1/partner-profiles/{$profileId}/approve")
            ->assertOk()
            ->assertJsonPath('data.approval_status', 'approved');

        $this->assertNotNull(PartnerProfile::query()->findOrFail($profileId)->verified_at);

        $this->actingAs($admin)
            ->postJson("/api/v1/partner-profiles/{$profileId}/reject")
            ->assertOk()
            ->assertJsonPath('data.approval_status', 'rejected');

        $this->actingAs($admin)
            ->postJson("/api/v1/partner-profiles/{$profileId}/suspend")
            ->assertOk()
            ->assertJsonPath('data.approval_status', 'suspended');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/partner-profiles/{$profileId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('partner_profiles', ['id' => $profileId]);
    }

    public function test_non_admin_cannot_manage_partner_profiles(): void
    {
        $user = User::factory()->professional()->create();
        $profile = PartnerProfileFactory::new()->approved()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/partner-profiles', [
                'user_id' => User::factory()->professional()->create()->id,
                'company_name' => 'Denied',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson("/api/v1/partner-profiles/{$profile->id}")
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson("/api/v1/partner-profiles/{$profile->id}/approve")
            ->assertForbidden();
    }

    public function test_partner_profile_validation_rejects_unique_fk_and_invalid_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = PartnerProfileFactory::new()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/partner-profiles', [
                'user_id' => $existing->user_id,
                'company_name' => '',
                'logo_media_id' => 999999,
                'partner_tier' => 'bad',
                'plant_type_id' => 999999,
                'keywords' => 'bad',
                'contact_email' => 'bad-email',
                'website' => 'not-a-url',
                'founded_year' => 1700,
                'layout_template' => 'bad',
                'approval_status' => 'bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'company_name',
                'logo_media_id',
                'partner_tier',
                'plant_type_id',
                'keywords',
                'contact_email',
                'website',
                'founded_year',
                'layout_template',
                'approval_status',
            ]);
    }

    public function test_nested_product_endpoints_cover_visibility_validation_and_belonging(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $profile = PartnerProfileFactory::new()->approved()->create();
        $otherProfile = PartnerProfileFactory::new()->approved()->create();
        $inactive = PartnerProductFactory::new()->create(['partner_id' => $profile->id, 'is_active' => false]);
        $active = PartnerProductFactory::new()->create(['partner_id' => $profile->id, 'is_active' => true]);
        $otherProduct = PartnerProductFactory::new()->create(['partner_id' => $otherProfile->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/partner-profiles/{$profile->id}/products")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);

        $this->actingAs($admin)
            ->getJson("/api/v1/partner-profiles/{$profile->id}/products/{$otherProduct->id}")
            ->assertNotFound();

        $this->actingAs($user)
            ->deleteJson("/api/v1/partner-profiles/{$profile->id}/products/{$inactive->id}")
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson("/api/v1/partner-profiles/{$profile->id}/products", [
                'name' => '',
                'item_type' => 'bad',
                'image_media_id' => 999999,
                'datasheet_media_id' => 999999,
                'keywords' => 'bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'item_type', 'image_media_id', 'datasheet_media_id', 'keywords']);
    }

    public function test_nested_presentation_endpoints_cover_filtering_validation_and_belonging(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $profile = PartnerProfileFactory::new()->approved()->create();
        $otherProfile = PartnerProfileFactory::new()->approved()->create();
        $pending = PartnerPresentationFactory::new()->create(['partner_id' => $profile->id, 'status' => 'pending_approval']);
        $approved = PartnerPresentationFactory::new()->approved()->create(['partner_id' => $profile->id]);
        $otherPresentation = PartnerPresentationFactory::new()->create(['partner_id' => $otherProfile->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/partner-profiles/{$profile->id}/presentations")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id);

        $this->actingAs($admin)
            ->getJson("/api/v1/partner-profiles/{$profile->id}/presentations/{$otherPresentation->id}")
            ->assertNotFound();

        $this->actingAs($user)
            ->deleteJson("/api/v1/partner-profiles/{$profile->id}/presentations/{$pending->id}")
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson("/api/v1/partner-profiles/{$profile->id}/presentations", [
                'title' => '',
                'slug' => $approved->slug,
                'plant_type_id' => 999999,
                'page_count' => -1,
                'status' => 'bad',
                'approved_by' => 999999,
                'file_media_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'slug', 'plant_type_id', 'page_count', 'status', 'approved_by', 'file_media_id']);
    }

    public function test_nested_member_endpoints_cover_crud_validation_and_belonging(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $profile = PartnerProfileFactory::new()->approved()->create();
        $otherProfile = PartnerProfileFactory::new()->approved()->create();
        $member = PartnerMemberFactory::new()->create(['partner_id' => $profile->id]);
        $otherMember = PartnerMemberFactory::new()->create(['partner_id' => $otherProfile->id]);

        $this->actingAs($admin)
            ->getJson("/api/v1/partner-profiles/{$profile->id}/members")
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'partner_id', 'user_id', 'member_role', 'joined_at', 'status']]]);

        $this->actingAs($admin)
            ->getJson("/api/v1/partner-profiles/{$profile->id}/members/{$otherMember->id}")
            ->assertNotFound();

        $this->actingAs($user)
            ->deleteJson("/api/v1/partner-profiles/{$profile->id}/members/{$member->id}")
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson("/api/v1/partner-profiles/{$profile->id}/members", [
                'user_id' => 999999,
                'member_role' => 'bad',
                'joined_at' => 'bad-date',
                'status' => 'bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'member_role', 'joined_at', 'status']);
    }

    public function test_deleting_partner_profile_cascades_nested_records(): void
    {
        $profile = PartnerProfileFactory::new()->create();
        $product = PartnerProductFactory::new()->create(['partner_id' => $profile->id]);
        $presentation = PartnerPresentationFactory::new()->create(['partner_id' => $profile->id]);
        $member = PartnerMemberFactory::new()->create(['partner_id' => $profile->id]);

        $profile->delete();

        $this->assertDatabaseMissing('partner_products', ['id' => $product->id]);
        $this->assertDatabaseMissing('partner_presentations', ['id' => $presentation->id]);
        $this->assertDatabaseMissing('partner_members', ['id' => $member->id]);
    }

    public function test_admin_web_routes_require_admin_and_can_manage_profiles(): void
    {
        $this->get('/dashboard/partner-profiles')->assertRedirect('/login');

        $user = User::factory()->professional()->create();
        $this->actingAs($user)->get('/dashboard/partner-profiles')->assertForbidden();

        $admin = User::factory()->admin()->create();
        $profileUser = User::factory()->professional()->create();
        $plantType = $this->createPlantType();
        $profile = PartnerProfileFactory::new()->create(['plant_type_id' => $plantType->id]);

        $this->actingAs($admin)->get('/dashboard/partner-profiles')->assertOk();
        $this->actingAs($admin)->get('/dashboard/partner-profiles/create')->assertOk();
        $this->actingAs($admin)->get("/dashboard/partner-profiles/{$profile->id}")->assertOk();
        $this->actingAs($admin)->get("/dashboard/partner-profiles/{$profile->id}/edit")->assertOk();

        $this->actingAs($admin)
            ->post('/dashboard/partner-profiles', [
                'user_id' => $profileUser->id,
                'company_name' => 'Admin Partner',
                'partner_tier' => 'gold',
                'plant_type_id' => $plantType->id,
                'contact_email' => 'admin-partner@example.test',
                'website' => 'https://example.test',
                'layout_template' => 'layout_1',
                'feed_highlight_enabled' => '1',
                'approval_status' => 'pending',
            ])
            ->assertRedirect('/dashboard/partner-profiles');

        $this->assertDatabaseHas('partner_profiles', ['company_name' => 'Admin Partner']);

        $this->actingAs($admin)
            ->post("/dashboard/partner-profiles/{$profile->id}/approve")
            ->assertRedirect("/dashboard/partner-profiles/{$profile->id}");

        $this->assertSame('approved', $profile->fresh()->approval_status);
    }

    private function createPlantType(array $attributes = []): PlantType
    {
        return PlantType::query()->create(array_merge([
            'name' => 'Plant Type',
            'slug' => 'plant-type',
            'description' => null,
            'is_active' => true,
            'sort_order' => 10,
        ], $attributes));
    }

    private function createMediaFile(User $uploader): MediaFile
    {
        return MediaFile::query()->create([
            'uploader_id' => $uploader->id,
            'disk' => 'local',
            'path' => 'uploads/logo.png',
            'original_name' => 'logo.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'upload_context' => 'partner_asset',
            'file_category' => 'image',
            'sort_order' => 0,
            'is_watermarked' => false,
            'processing_status' => 'processed',
            'is_orphan' => true,
        ]);
    }
}
