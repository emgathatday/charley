<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('verification_reminder_schedules')) {
            Schema::create('verification_reminder_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('reminder_type', ['30_days_before', '7_days_before', 'expiry_day', 'expired_notice']);
                $table->timestamp('scheduled_at')->index();
                $table->timestamp('sent_at')->nullable();
                $table->enum('status', ['pending', 'sent', 'cancelled'])->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_reminder_schedules');
    }
};
