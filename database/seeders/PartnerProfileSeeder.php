<?php

namespace Database\Seeders;

use App\Models\MediaFile;
use App\Models\PartnerMember;
use App\Models\PartnerPresentation;
use App\Models\PartnerProduct;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\PlantType;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PartnerProfileSeeder extends Seeder
{
    public function run(): void
    {
        $plantTypes = PlantType::query()
            ->orderBy('sort_order')
            ->take(4)
            ->get();

        $tier = SubscriptionTier::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        if ($plantTypes->isEmpty()) {
            return;
        }

        $partnerDefinitions = app()->runningUnitTests() ? array_slice($this->partners(), 0, 1) : $this->partners();

        foreach ($partnerDefinitions as $index => $definition) {
            $user = $this->seedPartnerUser($definition);
            $media = $this->seedMediaFiles($user, $definition);
            $subscription = $tier ? $this->seedPartnerSubscription($user, $tier, $definition) : null;

            $primaryPlantType = $plantTypes->slice($index, 1)->first() ?? $plantTypes->first();

            $profile = PartnerProfile::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $definition['company_name'],
                    'logo_media_id' => $media['logo']->id,
                    'overview' => $definition['overview'],
                    'partner_tier' => $definition['partner_tier'],
                    'plant_type_id' => $primaryPlantType->id,
                    'company_type' => $definition['company_type'],
                    'active_partner_subscription_id' => $subscription?->id,
                    'keywords' => $definition['keywords'],
                    'references' => $definition['references'],
                    'contact_email' => $user->email,
                    'phone' => $definition['phone'],
                    'address' => $definition['address'],
                    'country' => $definition['country'],
                    'website' => $definition['website'],
                    'founded_year' => $definition['founded_year'],
                    'social_links' => $definition['social_links'],
                    'layout_template' => $definition['layout_template'],
                    'feed_highlight_enabled' => $definition['feed_highlight_enabled'],
                    'subscription_status' => $subscription?->status ?? 'inactive',
                    'subscription_expires_at' => $subscription?->ends_at,
                    'approval_status' => $definition['approval_status'],
                    'verified_at' => $definition['verified_at'],
                ]
            );

            $profile->forceFill([
                'company_name' => $definition['company_name'],
                'logo_media_id' => $media['logo']->id,
                'overview' => $definition['overview'],
                'partner_tier' => $definition['partner_tier'],
                'plant_type_id' => $primaryPlantType->id,
                'company_type' => $definition['company_type'],
                'active_partner_subscription_id' => $subscription?->id,
                'keywords' => $definition['keywords'],
                'references' => $definition['references'],
                'contact_email' => $user->email,
                'phone' => $definition['phone'],
                'address' => $definition['address'],
                'country' => $definition['country'],
                'website' => $definition['website'],
                'founded_year' => $definition['founded_year'],
                'social_links' => $definition['social_links'],
                'layout_template' => $definition['layout_template'],
                'feed_highlight_enabled' => $definition['feed_highlight_enabled'],
                'subscription_status' => $subscription?->status ?? 'inactive',
                'subscription_expires_at' => $subscription?->ends_at,
                'approval_status' => $definition['approval_status'],
                'verified_at' => $definition['verified_at'],
            ])->save();

            $this->seedProfilePlantTypes($profile, $plantTypes, $index);
            $this->seedProducts($profile, $media, $definition);
            $this->seedPresentations($profile, $media, $plantTypes, $definition);
            $this->seedMembers($profile, $user, $definition);
        }
    }

    private function seedPartnerUser(array $definition): User
    {
        $user = app()->runningUnitTests()
            ? User::query()->where('role', 'professional')->first()
            : null;

        if ($user !== null) {
            $user->forceFill([
                'status' => 'active',
                'is_verified' => true,
                'verified_at' => $user->verified_at ?? now()->subMonths(6),
                'verification_expires_at' => $user->verification_expires_at ?? now()->addYear(),
            ])->save();

            return $user;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $definition['email']],
            [
                'username' => $definition['username'],
                'first_name' => $definition['first_name'],
                'last_name' => $definition['last_name'],
                'password' => Hash::make('password'),
                'role' => 'partner',
                'status' => 'active',
                'is_verified' => true,
                'verified_at' => now()->subMonths(6),
                'verification_expires_at' => now()->addYear(),
            ]
        );

        $user->fill([
            'username' => $definition['username'],
            'first_name' => $definition['first_name'],
            'last_name' => $definition['last_name'],
            'role' => 'partner',
            'status' => 'active',
            'is_verified' => true,
            'verified_at' => $user->verified_at ?? now()->subMonths(6),
            'verification_expires_at' => now()->addYear(),
        ])->save();

        return $user;
    }

    private function seedMediaFiles(User $user, array $definition): array
    {
        return [
            'logo' => $this->seedMediaFile($user, $definition['slug'].'/logo.svg', $definition['company_name'].' logo.svg', 'image/svg+xml', 'image'),
            'product' => $this->seedMediaFile($user, $definition['slug'].'/product.png', $definition['product_name'].' image.png', 'image/png', 'image'),
            'datasheet' => $this->seedMediaFile($user, $definition['slug'].'/datasheet.pdf', $definition['product_name'].' datasheet.pdf', 'application/pdf', 'document'),
            'presentation' => $this->seedMediaFile($user, $definition['slug'].'/presentation.pdf', $definition['presentation_title'].'.pdf', 'application/pdf', 'presentation'),
        ];
    }

    private function seedMediaFile(User $user, string $path, string $originalName, string $mimeType, string $category): MediaFile
    {
        $mediaFile = MediaFile::query()->firstOrCreate(
            ['path' => 'demo/partner-profiles/'.$path],
            [
                'uploader_id' => $user->id,
                'disk' => 's3',
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size' => 245760,
                'file_category' => $category,
                'sort_order' => 0,
                'is_watermarked' => false,
                'is_orphan' => false,
            ]
        );

        $mediaFile->fill([
            'uploader_id' => $user->id,
            'disk' => 's3',
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => 245760,
            'file_category' => $category,
            'is_orphan' => false,
        ])->save();

        return $mediaFile;
    }

    private function seedPartnerSubscription(User $user, SubscriptionTier $tier, array $definition): PartnerSubscription
    {
        return PartnerSubscription::query()->firstOrCreate(
            ['user_id' => $user->id, 'tier_id' => $tier->id, 'starts_at' => now()->startOfMonth()],
            [
                'status' => $definition['subscription_status'],
                'auto_renew' => true,
                'approved_by' => User::query()->where('role', 'admin')->value('id'),
                'approved_at' => now()->startOfMonth(),
                'ends_at' => now()->startOfMonth()->addMonth(),
            ]
        );
    }

    private function seedProfilePlantTypes(PartnerProfile $profile, $plantTypes, int $index): void
    {
        $selectedPlantTypes = $plantTypes->slice($index, 2)->values();

        if ($selectedPlantTypes->count() < 2) {
            $selectedPlantTypes = $plantTypes->take(2)->values();
        }

        foreach ($selectedPlantTypes as $sortOrder => $plantType) {
            DB::table('partner_profile_plant_type')->updateOrInsert(
                ['partner_profile_id' => $profile->id, 'plant_type_id' => $plantType->id],
                [
                    'is_primary' => $sortOrder === 0,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedProducts(PartnerProfile $profile, array $media, array $definition): void
    {
        $product = PartnerProduct::query()->firstOrCreate(
            ['partner_id' => $profile->id, 'name' => $definition['product_name']],
            [
                'category' => $definition['product_category'],
                'item_type' => $definition['product_type'],
                'description' => $definition['product_description'],
                'image_media_id' => $media['product']->id,
                'datasheet_media_id' => $media['datasheet']->id,
                'keywords' => $definition['product_keywords'],
                'is_active' => true,
            ]
        );

        $product->fill([
            'category' => $definition['product_category'],
            'item_type' => $definition['product_type'],
            'description' => $definition['product_description'],
            'image_media_id' => $media['product']->id,
            'datasheet_media_id' => $media['datasheet']->id,
            'keywords' => $definition['product_keywords'],
            'is_active' => true,
        ])->save();
    }

    private function seedPresentations(PartnerProfile $profile, array $media, $plantTypes, array $definition): void
    {
        $presentation = PartnerPresentation::query()->firstOrCreate(
            ['slug' => Str::slug($definition['presentation_title'])],
            [
                'partner_id' => $profile->id,
                'title' => $definition['presentation_title'],
                'description' => $definition['presentation_description'],
                'plant_type_id' => $plantTypes->first()->id,
                'equipment_category' => $definition['product_category'],
                'page_count' => $definition['page_count'],
                'download_allowed' => $definition['download_allowed'],
                'view_count' => $definition['view_count'],
                'status' => $definition['presentation_status'],
                'approved_by' => null,
                'approved_at' => $definition['presentation_status'] === 'approved' ? now()->subWeeks(2) : null,
                'rejection_reason' => $definition['presentation_status'] === 'rejected' ? 'Demo content needs clearer technical references.' : null,
                'is_ai_trainable' => $definition['presentation_status'] === 'approved',
                'file_media_id' => $media['presentation']->id,
            ]
        );

        $presentation->fill([
            'partner_id' => $profile->id,
            'title' => $definition['presentation_title'],
            'description' => $definition['presentation_description'],
            'plant_type_id' => $plantTypes->first()->id,
            'equipment_category' => $definition['product_category'],
            'page_count' => $definition['page_count'],
            'download_allowed' => $definition['download_allowed'],
            'view_count' => $definition['view_count'],
            'status' => $definition['presentation_status'],
            'approved_at' => $definition['presentation_status'] === 'approved' ? now()->subWeeks(2) : null,
            'rejection_reason' => $definition['presentation_status'] === 'rejected' ? 'Demo content needs clearer technical references.' : null,
            'is_ai_trainable' => $definition['presentation_status'] === 'approved',
            'file_media_id' => $media['presentation']->id,
        ])->save();
    }

    private function seedMembers(PartnerProfile $profile, User $partnerUser, array $definition): void
    {
        PartnerMember::query()->firstOrCreate(
            ['partner_id' => $profile->id, 'user_id' => $partnerUser->id],
            [
                'member_role' => 'manager',
                'joined_at' => now()->subMonths(8),
                'status' => 'active',
            ]
        );

        if (app()->runningUnitTests()) {
            return;
        }

        $staff = User::query()->firstOrCreate(
            ['email' => 'staff.'.$definition['email']],
            [
                'username' => 'staff_'.$definition['username'],
                'first_name' => 'Partner',
                'last_name' => 'Staff',
                'password' => Hash::make('password'),
                'role' => 'partner',
                'status' => 'active',
                'is_verified' => true,
                'verified_at' => now()->subMonths(3),
                'verification_expires_at' => now()->addYear(),
            ]
        );

        PartnerMember::query()->firstOrCreate(
            ['partner_id' => $profile->id, 'user_id' => $staff->id],
            [
                'member_role' => 'staff',
                'joined_at' => now()->subMonths(4),
                'status' => 'active',
            ]
        );
    }

    private function partners(): array
    {
        return [
            [
                'slug' => 'blue-harbor-process',
                'username' => 'blue_harbor_partner',
                'email' => 'partner.blueharbor@example.com',
                'first_name' => 'Blue Harbor',
                'last_name' => 'Partner',
                'company_name' => 'Blue Harbor Process Technologies',
                'company_type' => 'Licensor',
                'partner_tier' => 'gold',
                'overview' => 'Licensor focused on high-efficiency ammonia and methanol process packages.',
                'keywords' => ['licensor', 'ammonia', 'methanol', 'process technology'],
                'references' => [['project' => 'Coastal ammonia revamp', 'year' => 2024]],
                'phone' => '+84 28 5555 0101',
                'address' => '12 Nguyen Hue, District 1, Ho Chi Minh City',
                'country' => 'Vietnam',
                'website' => 'https://blueharbor.example.com',
                'founded_year' => 2009,
                'social_links' => ['linkedin' => 'https://linkedin.com/company/blue-harbor-process'],
                'layout_template' => 'layout_1',
                'feed_highlight_enabled' => true,
                'subscription_status' => 'active',
                'approval_status' => 'approved',
                'verified_at' => now()->subMonths(5),
                'product_name' => 'Low Energy Ammonia Loop Package',
                'product_category' => 'Process Package',
                'product_type' => 'technology',
                'product_description' => 'A demo process package for ammonia loop energy optimization.',
                'product_keywords' => ['ammonia', 'energy optimization', 'loop'],
                'presentation_title' => 'Ammonia Loop Optimization Handbook Brief',
                'presentation_description' => 'Approved demo presentation for ammonia loop optimization.',
                'presentation_status' => 'approved',
                'page_count' => 24,
                'download_allowed' => true,
                'view_count' => 128,
            ],
            [
                'slug' => 'nova-catalyst-systems',
                'username' => 'nova_catalyst_partner',
                'email' => 'partner.novacatalyst@example.com',
                'first_name' => 'Nova Catalyst',
                'last_name' => 'Partner',
                'company_name' => 'Nova Catalyst Systems',
                'company_type' => 'Catalyst supplier',
                'partner_tier' => 'gold',
                'overview' => 'Supplier of catalyst loading services and performance monitoring packages.',
                'keywords' => ['catalyst', 'supplier', 'monitoring', 'hydrogen'],
                'references' => [['project' => 'Hydrogen reformer catalyst replacement', 'year' => 2025]],
                'phone' => '+65 5555 0202',
                'address' => '88 Market Street, Singapore',
                'country' => 'Singapore',
                'website' => 'https://novacatalyst.example.com',
                'founded_year' => 2014,
                'social_links' => ['linkedin' => 'https://linkedin.com/company/nova-catalyst-systems'],
                'layout_template' => 'layout_2',
                'feed_highlight_enabled' => false,
                'subscription_status' => 'active',
                'approval_status' => 'approved',
                'verified_at' => now()->subMonths(2),
                'product_name' => 'Reformer Catalyst Health Audit',
                'product_category' => 'Catalyst Service',
                'product_type' => 'service',
                'product_description' => 'A demo catalyst inspection and health audit service package.',
                'product_keywords' => ['catalyst', 'inspection', 'reformer'],
                'presentation_title' => 'Hydrogen Reformer Catalyst Care Guide',
                'presentation_description' => 'Pending demo presentation for catalyst care workflows.',
                'presentation_status' => 'pending_approval',
                'page_count' => 18,
                'download_allowed' => false,
                'view_count' => 42,
            ],
        ];
    }
}
