<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_quiz_best_scores')) {
            return;
        }

        Schema::create('user_quiz_best_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->integer('best_score')->default(0);
            $table->foreignId('best_quiz_attempt_id')->nullable()->constrained('quiz_attempts')->nullOnDelete();
            $table->timestamp('achieved_at');
            $table->timestamps();

            $table->unique(['user_id', 'quiz_id']);
            $table->index(['quiz_id', 'best_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_quiz_best_scores');
    }
};