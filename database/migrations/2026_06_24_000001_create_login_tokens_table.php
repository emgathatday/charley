<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('login_tokens')) {
            Schema::create('login_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('token')->index();
                $table->enum('type', ['magic_link', 'otp', 'email_verify', 'password_reset']);
                $table->boolean('is_used')->default(false);
                $table->timestamp('expires_at');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_tokens');
    }
};
