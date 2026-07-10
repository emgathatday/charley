<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('library_item_hotspots')) {
            return;
        }

        Schema::create('library_item_hotspots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('library_item_id')->constrained('library_items')->cascadeOnDelete();
            $table->foreignId('knowledge_domain_id')->constrained('knowledge_domains')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->enum('shape_type', ['rect', 'polygon', 'circle'])->default('polygon');
            $table->json('coordinates');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['library_item_id', 'sort_order']);
            $table->index('knowledge_domain_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_item_hotspots');
    }
};