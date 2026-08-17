<?php

namespace App\Actions\Admin\Iam;

use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UpdatePartnerAction
{
    public function execute(Request $request, User $user): User
    {
        $data = $request->validate($this->rules($user));

        DB::transaction(function () use ($data, $request, $user): void {
            $isApproved = $data['approval_status'] === 'approved';
            $logoFile = $request->file('logo_file');

            $user->forceFill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'username' => $data['username'] ?: $user->username,
                'role' => 'partner',
                'status' => $data['status'],
                'is_verified' => $isApproved,
                'verified_at' => $isApproved ? ($user->verified_at ?? now()) : null,
                'verification_expires_at' => null,
            ])->save();

            $subscription = null;
            $subscriptionStatus = $data['subscription_status'];
            $profile = PartnerProfile::query()->where('user_id', $user->id)->first();
            if (($data['subscription_tier_id'] ?? null) !== null && $data['subscription_tier_id'] !== '') {
                $subscription = PartnerSubscription::query()
                    ->where('user_id', $user->id)
                    ->when($profile?->active_partner_subscription_id, fn (Builder $query, int $subscriptionId) => $query->where('id', $subscriptionId))
                    ->latest('id')
                    ->first();

                if (! $subscription) {
                    $subscription = PartnerSubscription::query()->create([
                        'user_id' => $user->id,
                        'tier_id' => (int) $data['subscription_tier_id'],
                        'status' => $subscriptionStatus === 'inactive' ? 'pending_approval' : $subscriptionStatus,
                        'approved_by' => $isApproved ? $request->user()?->id : null,
                        'approved_at' => $isApproved ? now() : null,
                        'starts_at' => $subscriptionStatus === 'active' ? now()->startOfDay() : null,
                        'ends_at' => null,
                        'auto_renew' => false,
                    ]);
                } else {
                    $subscription->forceFill([
                        'tier_id' => (int) $data['subscription_tier_id'],
                        'status' => $subscriptionStatus === 'inactive' ? 'pending_approval' : $subscriptionStatus,
                        'approved_by' => $isApproved ? ($subscription->approved_by ?? $request->user()?->id) : $subscription->approved_by,
                        'approved_at' => $isApproved ? ($subscription->approved_at ?? now()) : $subscription->approved_at,
                        'starts_at' => $subscriptionStatus === 'active' ? ($subscription->starts_at ?? now()->startOfDay()) : $subscription->starts_at,
                    ])->save();
                }
            }

            $profileData = [
                'company_name' => $data['company_name'],
                'overview' => $data['company_overview'] ?? null,
                'plant_type_id' => $data['plant_type_id'] ?? null,
                'keywords' => $this->commaSeparatedArray($data['keywords'] ?? null),
                'contact_email' => $data['public_contact_email'] ?? $data['email'],
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'website' => $data['website'] ?? null,
                'founded_year' => $data['founded_year'] ?? null,
                'layout_template' => $data['layout_template'],
                'feed_highlight_enabled' => $request->boolean('feed_highlight_enabled'),
                'subscription_status' => $subscription ? $subscription->status : 'inactive',
                'subscription_expires_at' => $subscription?->ends_at,
                'approval_status' => $data['approval_status'],
                'verified_at' => $isApproved ? ($profile?->verified_at ?? now()) : null,
                'active_partner_subscription_id' => $subscription?->id,
            ];

            if ($logoFile instanceof UploadedFile) {
                $logoMedia = $this->storePartnerLogo($logoFile, $request->user()?->id);
                $profileData['logo_media_id'] = $logoMedia?->id;
            }

            if ($profile) {
                $profile->forceFill($profileData)->save();
            } else {
                $profile = PartnerProfile::query()->create($profileData + ['user_id' => $user->id]);
            }

            if (isset($logoMedia) && $logoMedia) {
                $logoMedia->forceFill([
                    'attachable_type' => PartnerProfile::class,
                    'attachable_id' => $profile->id,
                    'is_orphan' => false,
                ])->save();
            }
        });

        return $user;
    }

    private function rules(User $user): array
    {
        $plantTypeIds = Schema::hasTable('plant_types') ? PlantType::query()->active()->sorted()->pluck('id')->map(fn ($id) => (int) $id)->all() : [];

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'company_overview' => ['nullable', 'string', 'max:400'],
            'plant_type_id' => ['nullable', 'integer', Rule::in($plantTypeIds)],
            'country' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
            'approval_status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'suspended'])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'public_contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'layout_template' => ['required', Rule::in(['layout_1', 'layout_2', 'layout_3'])],
            'feed_highlight_enabled' => ['nullable', 'boolean'],
            'subscription_tier_id' => ['nullable', Rule::exists('subscription_tiers', 'id')->where('is_active', true)],
            'subscription_status' => ['required', Rule::in(['inactive', 'pending_approval', 'active', 'suspended', 'expired', 'cancelled'])],
            'status' => ['required', Rule::in(['active', 'suspended', 'frozen'])],
            'logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
        ];
    }

    private function storePartnerLogo(?UploadedFile $file, ?int $uploaderId): ?MediaFile
    {
        if (! $file) {
            return null;
        }

        $path = $file->store('partner-logos', 'public');

        return MediaFile::create([
            'uploader_id' => $uploaderId,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'upload_context' => 'partner_asset',
            'file_category' => 'image',
            'sort_order' => 0,
            'is_watermarked' => false,
            'processing_status' => 'processed',
            'is_orphan' => true,
        ]);
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
