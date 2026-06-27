<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_activity_feed')) {
            Schema::create('user_activity_feed', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
                $table->enum('activity_type', ['asked_question', 'answered_question', 'answer_accepted', 'contribution_approved', 'poll_voted', 'event_registered', 'library_uploaded', 'connection_made']);
                $table->string('subject_type')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->boolean('is_public')->default(true);
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_feed');
    }
};
