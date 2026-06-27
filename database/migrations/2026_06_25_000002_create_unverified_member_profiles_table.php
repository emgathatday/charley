<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unverified_member_profiles')) {
            Schema::create('unverified_member_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique();
                $table->foreignId('photo_media_id')->nullable();
                $table->text('bio')->nullable();
                $table->string('current_institution')->nullable();
                $table->string('field_of_study')->nullable();
                $table->integer('experience_years')->nullable();
                $table->text('education')->nullable();
                $table->json('references')->nullable();
                $table->json('expertise_tags')->nullable();
                $table->json('searchable_keywords')->nullable();
                $table->boolean('is_discoverable')->default(true);
                $table->json('privacy_settings')->default(new Expression("'{}'::json"));
                $table->json('notification_preferences')->default(new Expression("'{}'::json"));
                $table->string('linkedin_url')->nullable();
                $table->enum('job_availability', ['open', 'not_looking', 'open_to_opportunities'])->nullable();
                $table->boolean('verification_intent')->default(false);
                $table->timestamps();

                $table->index(['is_discoverable', 'job_availability']);
                $table->index('current_institution');
                $table->index('verification_intent');

                if (Schema::hasTable('users')) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                }

                if (Schema::hasTable('media_files')) {
                    $table->foreign('photo_media_id')->references('id')->on('media_files')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unverified_member_profiles');
    }
};
