<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_profiles')) {
            return;
        }

        Schema::create('partner_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->foreignId('logo_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->text('overview')->nullable();
            $table->enum('partner_tier', ['gold', 'diamond', 'platinum'])->nullable();
            $table->foreignId('plant_type_id')->nullable()->constrained('plant_types')->nullOnDelete();
            $table->json('keywords')->nullable();
            $table->json('references')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('website')->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->json('social_links')->nullable();
            $table->enum('layout_template', ['layout_1', 'layout_2', 'layout_3'])->default('layout_1');
            $table->boolean('feed_highlight_enabled')->default(true);
            $table->string('subscription_status')->default('inactive');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_profiles');
    }
};
