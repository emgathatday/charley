<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partner_profiles') || ! Schema::hasTable('partner_subscriptions') || ! Schema::hasColumn('partner_profiles', 'active_partner_subscription_id')) {
            return;
        }

        DB::table('partner_profiles')
            ->select('id', 'user_id')
            ->orderBy('id')
            ->chunkById(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    $subscription = DB::table('partner_subscriptions')
                        ->where('user_id', $profile->user_id)
                        ->where('status', 'active')
                        ->orderByDesc('starts_at')
                        ->orderByDesc('id')
                        ->first(['id', 'status', 'ends_at']);

                    if (! $subscription) {
                        continue;
                    }

                    DB::table('partner_profiles')
                        ->where('id', $profile->id)
                        ->update([
                            'active_partner_subscription_id' => $subscription->id,
                            'subscription_status' => $subscription->status,
                            'subscription_expires_at' => $subscription->ends_at,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('partner_profiles') || ! Schema::hasColumn('partner_profiles', 'active_partner_subscription_id')) {
            return;
        }

        DB::table('partner_profiles')->update(['active_partner_subscription_id' => null]);
    }
};
