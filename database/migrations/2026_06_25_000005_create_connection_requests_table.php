<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('connection_requests')) {
            Schema::create('connection_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requester_id');
                $table->foreignId('target_user_id');
                $table->text('reason')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->foreignId('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_note')->nullable();
                $table->foreignId('connection_id')->nullable();
                $table->timestamps();

                $table->index('requester_id');
                $table->index('target_user_id');
                $table->index('status');
                $table->index(['requester_id', 'target_user_id', 'status']);
                $table->index('connection_id');

                if (Schema::hasTable('users')) {
                    $table->foreign('requester_id')->references('id')->on('users')->cascadeOnDelete();
                    $table->foreign('target_user_id')->references('id')->on('users')->cascadeOnDelete();
                    $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
                }

                if (Schema::hasTable('connections')) {
                    $table->foreign('connection_id')->references('id')->on('connections')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_requests');
    }
};
