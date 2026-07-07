<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('library_access_logs')) {
            return;
        }

        Schema::create('library_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('library_item_id')->constrained('library_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', ['view', 'download']);
            $table->string('ip_address');
            $table->timestamp('created_at')->index();

            $table->index(['library_item_id', 'action']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_access_logs');
    }
};
