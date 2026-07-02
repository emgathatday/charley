<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_revisions')) {
            return;
        }

        Schema::create('page_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->json('content_blocks');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('change_summary')->nullable();
            $table->timestamp('created_at');

            $table->index(['page_id', 'created_at']);
            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_revisions');
    }
};
