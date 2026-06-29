<?php

namespace Tests\Unit;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerProfileModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_profile_casts_json_booleans_and_dates(): void
    {
        $profile = PartnerProfileFactory::new()->create([
            'keywords' => ['licensor'],
            'references' => [['project' => 'Reference']],
            'social_links' => ['linkedin' => 'https://example.test'],
            'feed_highlight_enabled' => 1,
            'subscription_expires_at' => now()->addMonth(),
            'verified_at' => now(),
        ]);

        $this->assertSame(['licensor'], $profile->keywords);
        $this->assertSame([['project' => 'Reference']], $profile->references);
        $this->assertTrue($profile->feed_highlight_enabled);
        $this->assertNotNull($profile->subscription_expires_at);
        $this->assertNotNull($profile->verified_at);
    }

    public function test_partner_profile_relations_and_scopes(): void
    {
        $plantType = PlantType::query()->create([
            'name' => 'Ammonia',
            'slug' => 'ammonia',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $approved = PartnerProfileFactory::new()->approved()->create([
            'plant_type_id' => $plantType->id,
            'feed_highlight_enabled' => true,
        ]);
        PartnerProfileFactory::new()->create([
            'approval_status' => 'pending',
            'feed_highlight_enabled' => false,
        ]);

        $product = PartnerProductFactory::new()->create(['partner_id' => $approved->id]);
        $presentation = PartnerPresentationFactory::new()->create(['partner_id' => $approved->id]);
        $member = PartnerMemberFactory::new()->create(['partner_id' => $approved->id]);

        $this->assertTrue($approved->user()->first() instanceof User);
        $this->assertTrue($approved->plantType()->first()->is($plantType));
        $this->assertTrue($approved->products()->first()->is($product));
        $this->assertTrue($approved->presentations()->first()->is($presentation));
        $this->assertTrue($approved->members()->first()->is($member));
        $this->assertSame([$approved->id], PartnerProfile::query()->approved()->pluck('id')->all());
        $this->assertSame([$approved->id], PartnerProfile::query()->highlighted()->pluck('id')->all());
    }

    public function test_partner_product_casts_and_active_scope(): void
    {
        $active = PartnerProductFactory::new()->create([
            'keywords' => ['service'],
            'is_active' => 1,
        ]);
        PartnerProductFactory::new()->create(['is_active' => false]);

        $this->assertSame(['service'], $active->keywords);
        $this->assertTrue($active->is_active);
        $this->assertSame([$active->id], PartnerProduct::query()->active()->pluck('id')->all());
        $this->assertTrue($active->partner()->first() instanceof PartnerProfile);
    }

    public function test_partner_presentation_casts_and_scopes(): void
    {
        $approved = PartnerPresentationFactory::new()->approved()->create([
            'page_count' => '12',
            'download_allowed' => 1,
            'view_count' => '4',
            'is_ai_trainable' => true,
        ]);
        PartnerPresentationFactory::new()->create([
            'status' => 'pending_approval',
            'is_ai_trainable' => false,
        ]);

        $this->assertSame(12, $approved->page_count);
        $this->assertTrue($approved->download_allowed);
        $this->assertSame(4, $approved->view_count);
        $this->assertTrue($approved->is_ai_trainable);
        $this->assertSame([$approved->id], PartnerPresentation::query()->approved()->pluck('id')->all());
        $this->assertSame([$approved->id], PartnerPresentation::query()->aiTrainable()->pluck('id')->all());
    }

    public function test_partner_member_casts_and_scopes(): void
    {
        $manager = PartnerMemberFactory::new()->manager()->create(['status' => 'active']);
        PartnerMemberFactory::new()->create(['member_role' => 'staff', 'status' => 'inactive']);

        $this->assertNotNull($manager->joined_at);
        $this->assertSame([$manager->id], PartnerMember::query()->active()->pluck('id')->all());
        $this->assertSame([$manager->id], PartnerMember::query()->managers()->pluck('id')->all());
        $this->assertTrue($manager->partner()->first() instanceof PartnerProfile);
        $this->assertTrue($manager->user()->first() instanceof User);
    }
}
