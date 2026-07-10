<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('handbook_related_items')) {
            return;
        }

        Schema::create('handbook_related_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('handbook_article_id');
            $table->string('relatable_type');
            $table->unsignedBigInteger('relatable_id');
            $table->enum('relation_type', ['calculation_tool', 'library_item', 'partner_presentation', 'ai_shortcut']);
            $table->integer('sort_order')->default(0);

            $table->index(['handbook_article_id', 'relation_type']);
            $table->index(['relatable_type', 'relatable_id']);
            $table->foreign('handbook_article_id')->references('id')->on('handbook_articles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handbook_related_items');
    }
};
