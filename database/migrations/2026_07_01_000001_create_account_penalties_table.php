<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('account_penalties')) {
            return;
        }

        Schema::create('account_penalties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action_type', [
                'warning',
                'temporary_suspension',
                'account_freeze',
                'unfreeze',
                'ban',
                'self_freeze',
                'self_unfreeze',
            ]);
            $table->text('reason');
            $table->json('evidence_ref')->nullable();
            $table->integer('duration_days')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_penalties');
    }
};
