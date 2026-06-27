<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('connections')) {
            Schema::create('connections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requester_id');
                $table->foreignId('receiver_id');
                $table->enum('status', ['pending', 'accepted', 'declined', 'blocked']);
                $table->enum('initiated_context', ['engineer_to_engineer', 'partner_to_engineer', 'engineer_to_partner']);
                $table->timestamp('declined_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('blocked_at')->nullable();
                $table->foreignId('blocked_by')->nullable();
                $table->timestamps();

                $table->unique(['requester_id', 'receiver_id']);
                $table->index(['receiver_id', 'status']);
                $table->index(['requester_id', 'status']);
                $table->index(['status', 'initiated_context']);

                if (Schema::hasTable('users')) {
                    $table->foreign('requester_id')->references('id')->on('users')->cascadeOnDelete();
                    $table->foreign('receiver_id')->references('id')->on('users')->cascadeOnDelete();
                    $table->foreign('blocked_by')->references('id')->on('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
