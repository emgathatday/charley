<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('knowledge_domains') || ! Schema::hasTable('plant_types')) {
            return;
        }

        if (! Schema::hasTable('knowledge_domain_plant_type')) {
            Schema::create('knowledge_domain_plant_type', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('knowledge_domain_id')->index();
                $table->foreignId('plant_type_id')->index();
                $table->boolean('is_primary')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['knowledge_domain_id', 'plant_type_id'], 'knowledge_domain_plant_type_unique');

                $table->foreign('knowledge_domain_id')
                    ->references('id')
                    ->on('knowledge_domains')
                    ->cascadeOnDelete();

                $table->foreign('plant_type_id')
                    ->references('id')
                    ->on('plant_types');
            });
        }

        $this->backfillLegacyPlantTypes();
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_domain_plant_type');
    }

    private function backfillLegacyPlantTypes(): void
    {
        if (! Schema::hasTable('knowledge_domain_plant_type')
            || ! Schema::hasColumn('knowledge_domains', 'plant_type_id')) {
            return;
        }

        DB::table('knowledge_domains')
            ->whereNotNull('plant_type_id')
            ->orderBy('id')
            ->each(function (object $domain): void {
                $exists = DB::table('knowledge_domain_plant_type')
                    ->where('knowledge_domain_id', $domain->id)
                    ->where('plant_type_id', $domain->plant_type_id)
                    ->exists();

                if ($exists) {
                    return;
                }

                DB::table('knowledge_domain_plant_type')->insert([
                    'knowledge_domain_id' => $domain->id,
                    'plant_type_id' => $domain->plant_type_id,
                    'is_primary' => true,
                    'sort_order' => 0,
                    'created_at' => $domain->created_at,
                    'updated_at' => $domain->updated_at,
                ]);
            });
    }
};
