<?php

namespace App\Actions\Admin\Iam;

use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorePartnerAction
{
    /**
     * @return array{0: User, 1: PartnerSubscription}
     */
    public function execute(Request $request): array
    {
        $data = $request->validate($this->rules());
        $data['keywords'] = $this->validatedKeywords($request, $data);

        $tier = SubscriptionTier::query()->active()->findOrFail($data['subscription_tier_id']);
        $activateAccount = $request->boolean('activate_account');
        $requiresEmailVerification = $request->boolean('require_email_verification');
        $adminId = $request->user()?->id;
        $logoFile = $request->file('logo_file');

        return DB::transaction(function () use ($data, $tier, $activateAccount, $requiresEmailVerification, $adminId, $logoFile): array {
            $startsAt = $this->subscriptionStartDate($data['subscription_starts_at'] ?? null, $activateAccount);
            $endsAt = $this->subscriptionEndDate($data['subscription_ends_at'] ?? null, $startsAt, $tier);
            $isVerified = ! $requiresEmailVerification;

            $user = User::create([
                'username' => $data['username'] ?? $this->uniqueUsername($data['email'], $data['company_name']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['temporary_password'] ?? Str::password(16)),
                'role' => 'partner',
                'is_verified' => $isVerified,
                'verified_at' => $isVerified ? now() : null,
                'verification_expires_at' => null,
                'status' => $activateAccount ? 'active' : 'frozen',
                'login_attempts' => 0,
                'mfa_enabled' => false,
            ]);

            $subscription = PartnerSubscription::create([
                'user_id' => $user->id,
                'tier_id' => $tier->id,
                'status' => $activateAccount ? 'active' : 'pending_approval',
                'auto_renew' => (bool) ($data['auto_renew'] ?? false),
                'approved_by' => $activateAccount ? $adminId : null,
                'approved_at' => $activateAccount ? now() : null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $logoMedia = $this->storePartnerLogo($logoFile, $adminId);
            $profile = PartnerProfile::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'logo_media_id' => $logoMedia?->id,
                'overview' => $data['company_overview'] ?? null,
                'active_partner_subscription_id' => $activateAccount ? $subscription->id : null,
                'plant_type_id' => $data['plant_type_id'] ?? null,
                'keywords' => $data['keywords'],
                'contact_email' => $data['public_contact_email'] ?? $data['email'],
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'website' => $data['website'] ?? null,
                'layout_template' => 'layout_1',
                'feed_highlight_enabled' => true,
                'subscription_status' => $subscription->status,
                'subscription_expires_at' => $subscription->ends_at,
                'approval_status' => $activateAccount ? 'approved' : 'pending',
                'verified_at' => $activateAccount ? now() : null,
            ]);
            $logoMedia?->forceFill(['attachable_type' => PartnerProfile::class, 'attachable_id' => $profile->id, 'is_orphan' => false])->save();

            if (($data['payment_amount'] ?? null) !== null && $data['payment_amount'] !== '') {
                $paymentStatus = $data['payment_status'] ?? 'pending';
                SubscriptionPayment::create([
                    'partner_subscription_id' => $subscription->id,
                    'amount' => $data['payment_amount'],
                    'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                    'period_start' => $data['period_start'] ?? $startsAt?->toDateString(),
                    'period_end' => $data['period_end'] ?? $endsAt?->toDateString(),
                    'status' => $paymentStatus,
                    'transaction_code' => $data['transaction_code'] ?? null,
                    'approved_by' => $paymentStatus === 'approved' ? $adminId : null,
                ]);
            }

            return [$user, $subscription];
        });
    }

    private function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'temporary_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'activate_account' => ['nullable', 'boolean'],
            'require_email_verification' => ['nullable', 'boolean'],
            'subscription_tier_id' => ['required', Rule::exists('subscription_tiers', 'id')->where('is_active', true)],
            'auto_renew' => ['nullable', 'boolean'],
            'subscription_starts_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date', 'after_or_equal:subscription_starts_at'],
            'payment_amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', Rule::in(['bank_transfer', 'manual_invoice', 'other'])],
            'payment_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'refunded'])],
            'transaction_code' => ['nullable', 'string', 'max:255'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'company_overview' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'public_contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'plant_type_id' => ['nullable', 'integer'],
            'keywords' => ['nullable', 'json'],
            'logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
        ];
    }

    private function validatedKeywords(Request $request, array $data): ?array
    {
        $keywords = $request->has('keywords') ? $this->keywordList($data['keywords'] ?? null) : null;

        if ($request->has('keywords') && $keywords === []) {
            throw ValidationException::withMessages([
                'keywords' => 'Add at least one keyword.',
            ]);
        }

        return $keywords;
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

    private function subscriptionStartDate(?string $submittedStartDate, bool $activateAccount): ?Carbon
    {
        if ($submittedStartDate !== null && $submittedStartDate !== '') {
            return Carbon::parse($submittedStartDate)->startOfDay();
        }

        return $activateAccount ? now()->startOfDay() : null;
    }

    private function subscriptionEndDate(?string $submittedEndDate, ?Carbon $startsAt, SubscriptionTier $tier): ?Carbon
    {
        if ($submittedEndDate !== null && $submittedEndDate !== '') {
            return Carbon::parse($submittedEndDate)->endOfDay();
        }

        if (! $startsAt) {
            return null;
        }

        if ($tier->duration_days) {
            return $startsAt->copy()->addDays((int) $tier->duration_days)->endOfDay();
        }

        return match ($tier->billing_cycle) {
            'monthly' => $startsAt->copy()->addMonth()->endOfDay(),
            'yearly' => $startsAt->copy()->addYear()->endOfDay(),
            default => null,
        };
    }

    private function keywordList(?string $keywords): array
    {
        $decoded = json_decode((string) $keywords, true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn (mixed $keyword) => trim((string) $keyword))
            ->filter()
            ->unique(fn (string $keyword) => mb_strtolower($keyword))
            ->values()
            ->all();
    }

    private function uniqueUsername(string $email, string $fallback): string
    {
        $base = Str::slug(Str::before($email, '@') ?: $fallback, '_') ?: 'partner';
        $username = $base;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'_'.++$suffix;
        }

        return $username;
    }
}
