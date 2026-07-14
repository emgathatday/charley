<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('domain_rank_tiers')) {
            return;
        }

        Schema::create('domain_rank_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_domain_id')->constrained('knowledge_domains')->cascadeOnDelete();
            $table->string('name');
            $table->integer('min_points');
            $table->string('badge_icon')->nullable();
            $table->integer('sort_order');
            $table->timestamps();

            $table->index(['knowledge_domain_id', 'sort_order']);
            $table->index(['knowledge_domain_id', 'min_points']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_rank_tiers');
    }
};