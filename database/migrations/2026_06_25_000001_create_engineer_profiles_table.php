<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('engineer_profiles')) {
            Schema::create('engineer_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique();
                $table->foreignId('photo_media_id')->nullable();
                $table->text('bio')->nullable();
                $table->string('current_company')->nullable();
                $table->string('position')->nullable();
                $table->string('plant_name')->nullable();
                $table->integer('experience_years')->nullable();
                $table->text('education')->nullable();
                $table->json('expertise_tags')->nullable();
                $table->json('industry_specialization')->nullable();
                $table->json('searchable_keywords')->nullable();
                $table->json('references')->nullable();
                $table->string('phone')->nullable();
                $table->string('linkedin_url')->nullable();
                $table->enum('job_availability', ['open', 'not_looking', 'open_to_opportunities'])->nullable();
                $table->integer('reputation_points')->default(0);
                $table->json('reputation_breakdown')->nullable();
                $table->integer('ai_usage_count')->default(0);
                $table->boolean('is_discoverable')->default(true);
                $table->json('privacy_settings')->default('{}');
                $table->json('notification_preferences')->default('{}');
                $table->foreignId('verification_document_media_id')->nullable();
                $table->timestamp('verification_renewed_at')->nullable();
                $table->timestamp('renewal_reminder_sent_at')->nullable();
                $table->timestamps();

                $table->index(['is_discoverable', 'job_availability']);
                $table->index('reputation_points');
                $table->index('current_company');

                if (Schema::hasTable('users')) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                }

                if (Schema::hasTable('media_files')) {
                    $table->foreign('photo_media_id')->references('id')->on('media_files')->nullOnDelete();
                    $table->foreign('verification_document_media_id')->references('id')->on('media_files')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('engineer_profiles');
    }
};
