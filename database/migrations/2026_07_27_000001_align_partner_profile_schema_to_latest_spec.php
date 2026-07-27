<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->alignPartnerProfilesTable();
        $this->alignPartnerProductMediaColumns();
        $this->alignPartnerPresentationMediaColumn();
        $this->createPartnerProfilePlantTypeTable();
        $this->backfillLegacyPartnerPlantTypes();
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_profile_plant_type');

        if (Schema::hasTable('partner_profiles') && Schema::hasColumn('partner_profiles', 'company_type')) {
            Schema::table('partner_profiles', function (Blueprint $table): void {
                $table->dropColumn('company_type');
            });
        }
    }

    private function alignPartnerProfilesTable(): void
    {
        if (! Schema::hasTable('partner_profiles')) {
            return;
        }

        Schema::table('partner_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('partner_profiles', 'company_type')) {
                $table->string('company_type')->nullable()->after('overview');
            }

            if (! Schema::hasColumn('partner_profiles', 'logo_media_id')) {
                $table->foreignId('logo_media_id')->nullable()->after('company_name');
            }

            if (! Schema::hasColumn('partner_profiles', 'active_partner_subscription_id')) {
                $table->foreignId('active_partner_subscription_id')->nullable()->after('company_type');
            }
        });

        $this->addForeignIfMissing(
            'partner_profiles',
            'logo_media_id',
            'media_files',
            'partner_profiles_logo_media_id_foreign',
            'set null'
        );

        $this->addForeignIfMissing(
            'partner_profiles',
            'active_partner_subscription_id',
            'partner_subscriptions',
            'partner_profiles_active_partner_subscription_id_foreign',
            'set null'
        );
    }

    private function alignPartnerProductMediaColumns(): void
    {
        if (! Schema::hasTable('partner_products')) {
            return;
        }

        Schema::table('partner_products', function (Blueprint $table): void {
            if (! Schema::hasColumn('partner_products', 'image_media_id')) {
                $table->foreignId('image_media_id')->nullable()->after('description');
            }

            if (! Schema::hasColumn('partner_products', 'datasheet_media_id')) {
                $table->foreignId('datasheet_media_id')->nullable()->after('image_media_id');
            }
        });

        $this->addForeignIfMissing(
            'partner_products',
            'image_media_id',
            'media_files',
            'partner_products_image_media_id_foreign',
            'set null'
        );

        $this->addForeignIfMissing(
            'partner_products',
            'datasheet_media_id',
            'media_files',
            'partner_products_datasheet_media_id_foreign',
            'set null'
        );
    }

    private function alignPartnerPresentationMediaColumn(): void
    {
        if (! Schema::hasTable('partner_presentations')) {
            return;
        }

        Schema::table('partner_presentations', function (Blueprint $table): void {
            if (! Schema::hasColumn('partner_presentations', 'file_media_id')) {
                $table->foreignId('file_media_id')->nullable()->after('is_ai_trainable');
            }
        });

        $this->addForeignIfMissing(
            'partner_presentations',
            'file_media_id',
            'media_files',
            'partner_presentations_file_media_id_foreign',
            'set null'
        );
    }

    private function createPartnerProfilePlantTypeTable(): void
    {
        if (! Schema::hasTable('partner_profiles') || ! Schema::hasTable('plant_types')) {
            return;
        }

        if (Schema::hasTable('partner_profile_plant_type')) {
            return;
        }

        Schema::create('partner_profile_plant_type', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_profile_id')->index();
            $table->foreignId('plant_type_id')->index();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['partner_profile_id', 'plant_type_id'], 'partner_profile_plant_type_unique');
            $table->index(['plant_type_id', 'is_primary'], 'partner_profile_plant_type_filter_index');

            $table->foreign('partner_profile_id')
                ->references('id')
                ->on('partner_profiles')
                ->cascadeOnDelete();

            $table->foreign('plant_type_id')
                ->references('id')
                ->on('plant_types')
                ->cascadeOnDelete();
        });
    }

    private function backfillLegacyPartnerPlantTypes(): void
    {
        if (! Schema::hasTable('partner_profiles')
            || ! Schema::hasTable('partner_profile_plant_type')
            || ! Schema::hasColumn('partner_profiles', 'plant_type_id')) {
            return;
        }

        DB::table('partner_profiles')
            ->whereNotNull('plant_type_id')
            ->orderBy('id')
            ->each(function (object $profile): void {
                $exists = DB::table('partner_profile_plant_type')
                    ->where('partner_profile_id', $profile->id)
                    ->where('plant_type_id', $profile->plant_type_id)
                    ->exists();

                if (! $exists) {
                    DB::table('partner_profile_plant_type')->insert([
                        'partner_profile_id' => $profile->id,
                        'plant_type_id' => $profile->plant_type_id,
                        'is_primary' => true,
                        'sort_order' => 0,
                        'created_at' => $profile->created_at,
                        'updated_at' => $profile->updated_at,
                    ]);
                }
            });
    }

    private function addForeignIfMissing(
        string $table,
        string $column,
        string $referencesTable,
        string $constraintName,
        string $onDelete
    ): void {
        if (! Schema::hasTable($referencesTable)
            || ! Schema::hasColumn($table, $column)
            || $this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use (
            $column,
            $referencesTable,
            $constraintName,
            $onDelete
        ): void {
            $foreign = $blueprint->foreign($column, $constraintName)
                ->references('id')
                ->on($referencesTable);

            if ($onDelete === 'set null') {
                $foreign->nullOnDelete();
            } elseif ($onDelete === 'cascade') {
                $foreign->cascadeOnDelete();
            }
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        if (DB::getDriverName() !== 'pgsql') {
            return true;
        }

        return DB::table('information_schema.table_constraints')
            ->where('table_schema', 'public')
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
