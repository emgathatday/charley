<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('knowledge_domains') || Schema::hasColumn('knowledge_domains', 'quiz_question_count')) {
            return;
        }

        Schema::table('knowledge_domains', function (Blueprint $table): void {
            $table->integer('quiz_question_count')->default(50)->after('total_question_count');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('knowledge_domains') || ! Schema::hasColumn('knowledge_domains', 'quiz_question_count')) {
            return;
        }

        Schema::table('knowledge_domains', function (Blueprint $table): void {
            $table->dropColumn('quiz_question_count');
        });
    }
};