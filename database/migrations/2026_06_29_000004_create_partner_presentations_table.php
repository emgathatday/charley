<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_presentations')) {
            return;
        }

        Schema::create('partner_presentations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partner_profiles')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('plant_type_id')->nullable()->constrained('plant_types')->nullOnDelete();
            $table->string('equipment_category')->nullable();
            $table->integer('page_count')->nullable();
            $table->boolean('download_allowed')->default(false);
            $table->integer('view_count')->default(0);
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->default('pending_approval');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_ai_trainable')->default(false);
            $table->foreignId('file_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_presentations');
    }
};
