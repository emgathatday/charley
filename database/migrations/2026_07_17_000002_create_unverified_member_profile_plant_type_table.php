<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unverified_member_profile_plant_type')) {
            Schema::create('unverified_member_profile_plant_type', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('unverified_member_profile_id')->index();
                $table->foreignId('plant_type_id')->index();
                $table->boolean('is_primary')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['unverified_member_profile_id', 'plant_type_id'], 'unverified_member_profile_plant_type_unique');
                $table->index(['plant_type_id', 'is_primary'], 'unverified_member_profile_plant_type_filter_index');

                $table->foreign('unverified_member_profile_id')
                    ->references('id')
                    ->on('unverified_member_profiles')
                    ->cascadeOnDelete();

                $table->foreign('plant_type_id')
                    ->references('id')
                    ->on('plant_types')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unverified_member_profile_plant_type');
    }
};
