<?php

namespace Database\Seeders;

use App\Models\PlantType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class PartnerProfileSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();
        $plantType = PlantType::query()->orderBy('sort_order')->first();

        if (! $user || ! $plantType) {
            return;
        }

        $partnerProfile = new class extends Model
        {
            protected $table = 'partner_profiles';

            protected $guarded = [];

            protected $casts = [
                'keywords' => 'array',
                'references' => 'array',
                'social_links' => 'array',
                'feed_highlight_enabled' => 'boolean',
                'subscription_expires_at' => 'datetime',
                'verified_at' => 'datetime',
            ];
        };

        $partnerProduct = new class extends Model
        {
            protected $table = 'partner_products';

            protected $guarded = [];

            protected $casts = [
                'keywords' => 'array',
                'is_active' => 'boolean',
            ];
        };

        $partnerPresentation = new class extends Model
        {
            protected $table = 'partner_presentations';

            protected $guarded = [];

            protected $casts = [
                'download_allowed' => 'boolean',
                'approved_at' => 'datetime',
                'is_ai_trainable' => 'boolean',
            ];
        };

        $partnerMember = new class extends Model
        {
            protected $table = 'partner_members';

            public $timestamps = false;

            protected $guarded = [];

            protected $casts = [
                'joined_at' => 'datetime',
            ];
        };

        $profile = $partnerProfile->newQuery()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => 'Demo Process Technology Partner',
                'overview' => 'Demo partner profile for process technology products and presentations.',
                'partner_tier' => 'gold',
                'plant_type_id' => $plantType->id,
                'keywords' => ['licensor', 'technology', 'process'],
                'references' => [['project' => 'Demo Reference Unit', 'year' => 2024]],
                'contact_email' => 'partner@example.com',
                'country' => 'Vietnam',
                'website' => 'https://example.com',
                'founded_year' => 2010,
                'social_links' => ['linkedin' => 'https://example.com/company/demo-partner'],
                'layout_template' => 'layout_1',
                'feed_highlight_enabled' => true,
                'subscription_status' => 'inactive',
                'approval_status' => 'approved',
                'verified_at' => now(),
            ]
        );

        $partnerProduct->newQuery()->firstOrCreate(
            ['partner_id' => $profile->id, 'name' => 'Demo Catalyst Package'],
            [
                'category' => 'Catalyst',
                'item_type' => 'product',
                'description' => 'Demo catalyst package linked to the partner profile.',
                'keywords' => ['catalyst', 'package'],
                'is_active' => true,
            ]
        );

        $partnerPresentation->newQuery()->firstOrCreate(
            ['slug' => 'demo-process-technology-presentation'],
            [
                'partner_id' => $profile->id,
                'title' => 'Demo Process Technology Presentation',
                'description' => 'Demo technical presentation awaiting approval.',
                'plant_type_id' => $plantType->id,
                'equipment_category' => 'Process',
                'page_count' => 12,
                'download_allowed' => false,
                'view_count' => 0,
                'status' => 'pending_approval',
                'is_ai_trainable' => false,
            ]
        );

        $partnerMember->newQuery()->firstOrCreate(
            ['partner_id' => $profile->id, 'user_id' => $user->id],
            [
                'member_role' => 'manager',
                'joined_at' => now(),
                'status' => 'active',
            ]
        );
    }
}
