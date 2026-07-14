<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expertise_rank_tiers')) {
            return;
        }

        Schema::table('expertise_rank_tiers', function (Blueprint $table): void {
            if (! Schema::hasColumn('expertise_rank_tiers', 'status')) {
                $table->enum('status', ['active', 'draft', 'deleted'])
                    ->default('active')
                    ->after('required_mandatory_quiz_count')
                    ->index();
            }

            if (! Schema::hasColumn('expertise_rank_tiers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });

        if (Schema::hasColumn('expertise_rank_tiers', 'status') && Schema::hasColumn('expertise_rank_tiers', 'is_active')) {
            DB::table('expertise_rank_tiers')
                ->where('is_active', false)
                ->update(['status' => 'draft']);

            DB::table('expertise_rank_tiers')
                ->where('is_active', true)
                ->update(['status' => 'active']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('expertise_rank_tiers') || ! Schema::hasColumn('expertise_rank_tiers', 'status')) {
            return;
        }

        Schema::table('expertise_rank_tiers', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};