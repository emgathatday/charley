<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('handbook_articles')) {
            return;
        }

        Schema::create('handbook_articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id');
            $table->foreignId('user_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->text('optimization_guidance')->nullable();
            $table->json('failure_modes')->nullable();
            $table->enum('status', ['draft', 'published', 'archived']);
            $table->boolean('is_ai_trainable')->default(true);
            $table->json('ai_shortcut_config')->nullable();
            $table->integer('view_count')->default(0);
            $table->longText('process_description')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('is_ai_trainable');
            $table->foreign('category_id')->references('id')->on('handbook_categories');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handbook_articles');
    }
};
