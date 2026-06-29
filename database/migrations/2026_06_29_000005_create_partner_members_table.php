<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_members')) {
            return;
        }

        Schema::create('partner_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partner_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('member_role', ['manager', 'staff', 'viewer']);
            $table->timestamp('joined_at');
            $table->enum('status', ['active', 'inactive']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_members');
    }
};
