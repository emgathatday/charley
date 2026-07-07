<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('library_access_rules')) {
            return;
        }

        Schema::create('library_access_rules', function (Blueprint $table): void {
            $table->id();
            $table->enum('partner_tier', ['gold', 'diamond', 'platinum'])->unique();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_download')->default(false);
            $table->boolean('can_copy_paste')->default(false);
            $table->boolean('requires_watermark')->default(true);
            $table->integer('max_downloads_per_month')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_access_rules');
    }
};
