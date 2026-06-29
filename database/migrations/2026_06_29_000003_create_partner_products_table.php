<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_products')) {
            return;
        }

        Schema::create('partner_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partner_profiles')->cascadeOnDelete();
            $table->string('name')->index();
            $table->string('category')->nullable();
            $table->enum('item_type', ['product', 'service', 'technology'])->default('product');
            $table->text('description')->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->foreignId('datasheet_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->json('keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_products');
    }
};
