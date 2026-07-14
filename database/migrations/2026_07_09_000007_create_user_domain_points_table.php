<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_domain_points')) {
            return;
        }

        Schema::create('user_domain_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('knowledge_domain_id')->constrained('knowledge_domains')->cascadeOnDelete();
            $table->integer('total_points')->default(0);
            $table->foreignId('current_rank_tier_id')->nullable()->constrained('domain_rank_tiers')->nullOnDelete();
            $table->timestamp('last_recalculated_at');
            $table->timestamps();

            $table->unique(['user_id', 'knowledge_domain_id']);
            $table->index(['knowledge_domain_id', 'total_points']);
            $table->index('current_rank_tier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_domain_points');
    }
};