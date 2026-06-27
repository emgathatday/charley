<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_metas')) {
            Schema::create('user_metas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
                $table->string('key')->index();
                $table->text('value')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_metas');
    }
};
