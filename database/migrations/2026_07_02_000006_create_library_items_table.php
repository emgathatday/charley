<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('library_items')) {
            return;
        }

        Schema::create('library_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('library_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->foreignId('plant_type_id')->nullable()->constrained('plant_types')->nullOnDelete();
            $table->string('author')->nullable();
            $table->string('source')->nullable();
            $table->year('published_year')->nullable();
            $table->enum('access_level', ['public', 'member', 'professional_only', 'partner_only', 'admin_only'])->default('professional_only');
            $table->boolean('download_allowed')->default(false);
            $table->boolean('copy_paste_disabled')->default(false);
            $table->integer('download_count')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_ai_trainable')->default(true);
            $table->enum('content_type', ['article', 'video', 'document', 'presentation', 'case_study', 'safety_bulletin']);
            $table->enum('item_type', ['handbook', 'article', 'presentation', 'video', 'case_study', 'safety_bulletin', 'whitepaper'])->nullable();
            $table->integer('view_count')->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->year('year')->nullable();
            $table->foreignId('file_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index(['plant_type_id', 'status']);
            $table->index(['access_level', 'status']);
            $table->index(['content_type', 'status']);
            $table->index('is_ai_trainable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_items');
    }
};
