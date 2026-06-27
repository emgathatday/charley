<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('username')->unique();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->unique();
                $table->string('password');
                $table->enum('role', ['admin', 'unverified_member', 'professional', 'partner']);
                $table->enum('status', ['active', 'suspended', 'frozen'])->default('active');
                $table->boolean('is_verified')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('verification_expires_at')->nullable();
                $table->rememberToken();
                $table->timestamp('last_login_at')->nullable();
                $table->tinyInteger('login_attempts')->default(0);
                $table->timestamp('locked_until')->nullable();
                $table->boolean('mfa_enabled')->default(false);
                $table->string('mfa_secret')->nullable();
                $table->json('mfa_recovery_codes')->nullable();
                $table->timestamp('self_frozen_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
