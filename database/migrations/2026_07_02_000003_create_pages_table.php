<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages')) {
            return;
        }

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('content_blocks');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_system_page')->default(false);
            $table->integer('view_count')->default(0);
            $table->json('seo_meta')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('is_system_page');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
