<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_feed_cache')) {
            return;
        }

        Schema::create('user_feed_cache', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->index()->constrained('users')->cascadeOnDelete();
            $table->string('feedable_type')->index();
            $table->unsignedBigInteger('feedable_id')->index();
            $table->integer('priority_score')->default(0);
            $table->enum('source_reason', [
                'priority_rule',
                'followed_partner',
                'network_activity',
                'unanswered_question',
                'fresh_content',
                'admin_highlight',
            ]);
            $table->boolean('is_seen')->default(false);
            $table->timestamp('created_at')->index();
            $table->timestamp('expires_at')->index();

            $table->index(['user_id', 'is_seen']);
            $table->index(['user_id', 'expires_at']);
            $table->index(['feedable_type', 'feedable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feed_cache');
    }
};
