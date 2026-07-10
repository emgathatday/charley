<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('handbook_categories')) {
            return;
        }

        Schema::create('handbook_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('plant_type_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreignId('layout_image_media_id')->nullable();
            $table->json('map_coordinates')->nullable();
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['draft', 'published']);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
            $table->index(['plant_type_id', 'status']);
            $table->foreign('plant_type_id')->references('id')->on('plant_types')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('handbook_categories')->nullOnDelete();
            $table->foreign('layout_image_media_id')->references('id')->on('media_files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handbook_categories');
    }
};
