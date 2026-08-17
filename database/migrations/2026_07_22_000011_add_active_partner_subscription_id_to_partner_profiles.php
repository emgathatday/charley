<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partner_profiles')) {
            return;
        }

        Schema::table('partner_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('partner_profiles', 'active_partner_subscription_id')) {
                $table->foreignId('active_partner_subscription_id')
                    ->nullable()
                    ->after('overview')
                    ->constrained('partner_subscriptions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('partner_profiles') || ! Schema::hasColumn('partner_profiles', 'active_partner_subscription_id')) {
            return;
        }

        Schema::table('partner_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('active_partner_subscription_id');
        });
    }
};
