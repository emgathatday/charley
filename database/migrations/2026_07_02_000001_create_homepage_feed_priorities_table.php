<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('homepage_feed_priorities')) {
            return;
        }

        Schema::create('homepage_feed_priorities', function (Blueprint $table): void {
            $table->id();
            $table->enum('content_type', [
                'partner_announcement',
                'network_post',
                'unanswered_question',
                'library_item',
                'handbook_article',
                'event',
                'job',
                'poll',
                'service',
            ])->unique();
            $table->integer('priority_weight')->default(0);
            $table->boolean('is_highlighted')->default(false);
            $table->string('highlight_color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_feed_priorities');
    }
};
