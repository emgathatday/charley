<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('member_subscriptions')) {
            return;
        }

        Schema::create('member_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('member_subscription_plans')->cascadeOnDelete();
            $table->enum('status', ['active', 'expired', 'cancelled']);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('payment_method')->default('bank_transfer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_subscriptions');
    }
};
