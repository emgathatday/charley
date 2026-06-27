<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('search_index_entries')) {
            Schema::create('search_index_entries', function (Blueprint $table) {
                $table->id();
                $table->string('indexable_type')->index();
                $table->unsignedBigInteger('indexable_id')->index();
                $table->longText('searchable_text');
                $table->json('structured_data');
                $table->enum('search_context', ['expert_directory', 'partner_directory', 'global'])->index();
                $table->boolean('is_discoverable')->default(true);
                $table->timestamp('last_indexed_at');

                $table->index(['indexable_type', 'indexable_id']);
                $table->index(['search_context', 'is_discoverable']);
                $table->fullText('searchable_text');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_index_entries');
    }
};
