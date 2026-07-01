<?php

namespace Database\Seeders;

use App\Models\AnnouncementQuota;
use App\Models\PartnerMember;
use App\Models\PartnerPresentation;
use App\Models\PartnerProduct;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\PlantType;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoPartnerDashboardSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        if (! PlantType::query()->exists()) {
            $this->call(PlantTypeSeeder::class);
        }

        if (! SubscriptionTier::query()->exists()) {
            $this->call(SubscriptionSeeder::class);
        }

        $plantType = PlantType::query()->orderBy('sort_order')->orderBy('name')->first();
        $tier = SubscriptionTier::query()->where('name', 'diamond')->first()
            ?? SubscriptionTier::query()->orderByDesc('monthly_price')->first();

        if (! $plantType || ! $tier) {
            return;
        }

        $partnerUser = User::query()->firstOrNew(['email' => 'demo.partner@charley.local']);
        $partnerUser->forceFill([
            'username' => 'demo-partner',
            'first_name' => 'Demo',
            'last_name' => 'Partner',
            'password' => Hash::make('charley-demo-partner'),
            'role' => 'partner',
            'is_verified' => true,
            'verified_at' => $partnerUser->verified_at ?: now(),
            'status' => 'active',
            'login_attempts' => 0,
            'mfa_enabled' => false,
        ])->save();

        $adminUser = User::query()->where('role', 'admin')->orderBy('id')->first();
        $profile = PartnerProfile::query()->firstOrCreate(
            ['user_id' => $partnerUser->id],
            [
                'company_name' => 'Charley Demo Partner Co.',
                'overview' => 'Local-only demo partner account and records for dashboard UI checks.',
                'partner_tier' => 'diamond',
                'plant_type_id' => $plantType->id,
                'keywords' => ['demo', 'partner', 'dashboard'],
                'references' => [['project' => 'Local UI Check', 'year' => now()->year]],
                'contact_email' => 'demo.partner@charley.local',
                'phone' => '+84 000 000 000',
                'address' => 'Local development dataset',
                'country' => 'Vietnam',
                'website' => 'https://example.test/demo-partner',
                'founded_year' => 2026,
                'social_links' => ['linkedin' => 'https://example.test/demo-partner'],
                'layout_template' => 'layout_1',
                'feed_highlight_enabled' => true,
                'subscription_status' => 'active',
                'subscription_expires_at' => now()->addMonth(),
                'approval_status' => 'approved',
                'verified_at' => now(),
            ]
        );

        $profile->fill([
            'company_name' => 'Charley Demo Partner Co.',
            'partner_tier' => 'diamond',
            'plant_type_id' => $plantType->id,
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonth(),
            'approval_status' => 'approved',
            'verified_at' => $profile->verified_at ?: now(),
        ])->save();

        PartnerProduct::query()->firstOrCreate(
            ['partner_id' => $profile->id, 'name' => 'Demo Process Optimization Package'],
            [
                'category' => 'Technology',
                'item_type' => 'service',
                'description' => 'Local demo service record for dashboard table and detail checks.',
                'keywords' => ['demo', 'optimization', 'process'],
                'is_active' => true,
            ]
        );

        PartnerPresentation::query()->firstOrCreate(
            ['slug' => 'local-demo-partner-dashboard-presentation'],
            [
                'partner_id' => $profile->id,
                'title' => 'Local Demo Partner Dashboard Presentation',
                'description' => 'Local-only presentation row for admin UI checks.',
                'plant_type_id' => $plantType->id,
                'equipment_category' => 'Process Systems',
                'page_count' => 18,
                'download_allowed' => false,
                'view_count' => 7,
                'status' => 'approved',
                'approved_by' => $adminUser?->id,
                'approved_at' => now(),
                'is_ai_trainable' => false,
            ]
        );

        PartnerMember::query()->firstOrCreate(
            ['partner_id' => $profile->id, 'user_id' => $partnerUser->id],
            [
                'member_role' => 'manager',
                'joined_at' => now(),
                'status' => 'active',
            ]
        );

        $subscription = PartnerSubscription::query()->firstOrCreate(
            ['user_id' => $partnerUser->id, 'tier_id' => $tier->id, 'starts_at' => now()->startOfMonth()],
            [
                'status' => 'active',
                'approved_by' => $adminUser?->id,
                'approved_at' => now(),
                'ends_at' => now()->startOfMonth()->addMonth(),
            ]
        );

        SubscriptionPayment::query()->firstOrCreate(
            ['partner_subscription_id' => $subscription->id, 'transaction_code' => 'LOCAL-DEMO-PARTNER'],
            [
                'amount' => $tier->monthly_price,
                'payment_method' => 'bank_transfer',
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'status' => 'approved',
                'approved_by' => $adminUser?->id,
            ]
        );

        AnnouncementQuota::query()->firstOrCreate(
            ['user_id' => $partnerUser->id, 'period' => now()->format('Y-m')],
            [
                'used_count' => 1,
                'quota_limit' => $tier->announcement_limit,
            ]
        );
    }
}
