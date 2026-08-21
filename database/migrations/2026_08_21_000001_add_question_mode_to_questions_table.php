<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('questions') || Schema::hasColumn('questions', 'question_mode')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table): void {
            $table->enum('question_mode', ['community', 'admin_seed', 'admin_on_behalf'])
                ->default('community')
                ->after('is_anonymous');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('questions') || ! Schema::hasColumn('questions', 'question_mode')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn('question_mode');
        });
    }
};
