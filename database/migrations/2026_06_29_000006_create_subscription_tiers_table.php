<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_tiers')) {
            return;
        }

        Schema::create('subscription_tiers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('monthly_price', 12, 2);
            $table->integer('ai_monthly_limit');
            $table->enum('announcement_frequency', ['weekly', 'monthly']);
            $table->integer('announcement_limit');
            $table->boolean('can_host_webinar')->default(false);
            $table->boolean('can_initiate_message')->default(false);
            $table->boolean('can_create_poll')->default(false);
            $table->boolean('can_publish_events')->default(false);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_tiers');
    }
};
