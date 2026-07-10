<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('handbook_metadata')) {
            return;
        }

        Schema::create('handbook_metadata', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id');
            $table->enum('meta_type', ['iow', 'kpi', 'troubleshooting', 'equipment_spec', 'catalyst_info']);
            $table->string('meta_key');
            $table->text('meta_value');
            $table->enum('vector_status', ['pending', 'synced', 'failed']);
            $table->timestamps();

            $table->index('meta_key');
            $table->index(['article_id', 'meta_type']);
            $table->index('vector_status');
            $table->foreign('article_id')->references('id')->on('handbook_articles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handbook_metadata');
    }
};
