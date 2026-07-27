<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('engineer_profiles')) {
            Schema::table('engineer_profiles', function (Blueprint $table): void {
                if (! Schema::hasColumn('engineer_profiles', 'current_institution')) {
                    $table->string('current_institution')->nullable()->after('current_company');
                }

                if (! Schema::hasColumn('engineer_profiles', 'field_of_study')) {
                    $table->string('field_of_study')->nullable()->after('position');
                }

                if (! Schema::hasColumn('engineer_profiles', 'verification_intent')) {
                    $table->boolean('verification_intent')->default(false)->after('notification_preferences');
                }
            });

            $this->copyUnverifiedProfilesIntoEngineerProfiles();
            $this->copyUnverifiedProfilePlantTypes();
        }

        Schema::dropIfExists('unverified_member_profile_plant_type');
        Schema::dropIfExists('unverified_member_profiles');
    }

    public function down(): void
    {
        $this->restoreUnverifiedMemberProfilesTable();
        $this->restoreUnverifiedMemberProfilePlantTypeTable();

        if (Schema::hasTable('engineer_profiles')) {
            Schema::table('engineer_profiles', function (Blueprint $table): void {
                if (Schema::hasColumn('engineer_profiles', 'verification_intent')) {
                    $table->dropColumn('verification_intent');
                }

                if (Schema::hasColumn('engineer_profiles', 'field_of_study')) {
                    $table->dropColumn('field_of_study');
                }

                if (Schema::hasColumn('engineer_profiles', 'current_institution')) {
                    $table->dropColumn('current_institution');
                }
            });
        }
    }

    private function copyUnverifiedProfilesIntoEngineerProfiles(): void
    {
        if (! Schema::hasTable('unverified_member_profiles')) {
            return;
        }

        DB::table('unverified_member_profiles')
            ->orderBy('id')
            ->each(function (object $profile): void {
                $engineerProfile = DB::table('engineer_profiles')
                    ->where('user_id', $profile->user_id)
                    ->first();

                if ($engineerProfile === null) {
                    DB::table('engineer_profiles')->insert([
                        'user_id' => $profile->user_id,
                        'photo_media_id' => $profile->photo_media_id,
                        'bio' => $profile->bio,
                        'current_company' => null,
                        'current_institution' => $profile->current_institution,
                        'position' => null,
                        'field_of_study' => $profile->field_of_study,
                        'plant_name' => null,
                        'experience_years' => $profile->experience_years,
                        'education' => $profile->education,
                        'expertise_tags' => $profile->expertise_tags,
                        'industry_specialization' => null,
                        'searchable_keywords' => $profile->searchable_keywords,
                        'references' => $profile->references,
                        'phone' => null,
                        'linkedin_url' => $profile->linkedin_url,
                        'job_availability' => $profile->job_availability,
                        'reputation_points' => 0,
                        'reputation_breakdown' => null,
                        'ai_usage_count' => 0,
                        'is_discoverable' => $profile->is_discoverable,
                        'privacy_settings' => $profile->privacy_settings,
                        'notification_preferences' => $profile->notification_preferences,
                        'verification_intent' => $profile->verification_intent,
                        'verification_document_media_id' => null,
                        'verification_renewed_at' => null,
                        'renewal_reminder_sent_at' => null,
                        'created_at' => $profile->created_at,
                        'updated_at' => $profile->updated_at,
                    ]);

                    return;
                }

                DB::table('engineer_profiles')
                    ->where('id', $engineerProfile->id)
                    ->update([
                        'current_institution' => $engineerProfile->current_institution ?? $profile->current_institution,
                        'field_of_study' => $engineerProfile->field_of_study ?? $profile->field_of_study,
                        'verification_intent' => $engineerProfile->verification_intent || $profile->verification_intent,
                    ]);
            });
    }

    private function copyUnverifiedProfilePlantTypes(): void
    {
        if (! Schema::hasTable('unverified_member_profiles')
            || ! Schema::hasTable('unverified_member_profile_plant_type')
            || ! Schema::hasTable('engineer_profile_plant_type')) {
            return;
        }

        DB::table('unverified_member_profile_plant_type')
            ->join(
                'unverified_member_profiles',
                'unverified_member_profiles.id',
                '=',
                'unverified_member_profile_plant_type.unverified_member_profile_id'
            )
            ->join('engineer_profiles', 'engineer_profiles.user_id', '=', 'unverified_member_profiles.user_id')
            ->select([
                'engineer_profiles.id as engineer_profile_id',
                'unverified_member_profile_plant_type.plant_type_id',
                'unverified_member_profile_plant_type.is_primary',
                'unverified_member_profile_plant_type.sort_order',
                'unverified_member_profile_plant_type.created_at',
                'unverified_member_profile_plant_type.updated_at',
            ])
            ->orderBy('unverified_member_profile_plant_type.id')
            ->each(function (object $plantType): void {
                $exists = DB::table('engineer_profile_plant_type')
                    ->where('engineer_profile_id', $plantType->engineer_profile_id)
                    ->where('plant_type_id', $plantType->plant_type_id)
                    ->exists();

                if (! $exists) {
                    DB::table('engineer_profile_plant_type')->insert([
                        'engineer_profile_id' => $plantType->engineer_profile_id,
                        'plant_type_id' => $plantType->plant_type_id,
                        'is_primary' => $plantType->is_primary,
                        'sort_order' => $plantType->sort_order,
                        'created_at' => $plantType->created_at,
                        'updated_at' => $plantType->updated_at,
                    ]);
                }
            });
    }

    private function restoreUnverifiedMemberProfilesTable(): void
    {
        if (Schema::hasTable('unverified_member_profiles')) {
            return;
        }

        Schema::create('unverified_member_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->foreignId('photo_media_id')->nullable();
            $table->text('bio')->nullable();
            $table->string('current_institution')->nullable();
            $table->string('field_of_study')->nullable();
            $table->integer('experience_years')->nullable();
            $table->text('education')->nullable();
            $table->json('references')->nullable();
            $table->json('expertise_tags')->nullable();
            $table->json('searchable_keywords')->nullable();
            $table->boolean('is_discoverable')->default(true);
            $table->json('privacy_settings')->default('{}');
            $table->json('notification_preferences')->default('{}');
            $table->string('linkedin_url')->nullable();
            $table->enum('job_availability', ['open', 'not_looking', 'open_to_opportunities'])->nullable();
            $table->boolean('verification_intent')->default(false);
            $table->timestamps();

            $table->index(['is_discoverable', 'job_availability']);
            $table->index('current_institution');
            $table->index('verification_intent');

            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }

            if (Schema::hasTable('media_files')) {
                $table->foreign('photo_media_id')->references('id')->on('media_files')->nullOnDelete();
            }
        });
    }

    private function restoreUnverifiedMemberProfilePlantTypeTable(): void
    {
        if (Schema::hasTable('unverified_member_profile_plant_type')) {
            return;
        }

        Schema::create('unverified_member_profile_plant_type', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('unverified_member_profile_id')->index();
            $table->foreignId('plant_type_id')->index();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['unverified_member_profile_id', 'plant_type_id'], 'unverified_member_profile_plant_type_unique');
            $table->index(['plant_type_id', 'is_primary'], 'unverified_member_profile_plant_type_filter_index');

            if (Schema::hasTable('unverified_member_profiles')) {
                $table->foreign('unverified_member_profile_id')
                    ->references('id')
                    ->on('unverified_member_profiles')
                    ->cascadeOnDelete();
            }

            if (Schema::hasTable('plant_types')) {
                $table->foreign('plant_type_id')
                    ->references('id')
                    ->on('plant_types')
                    ->cascadeOnDelete();
            }
        });
    }
};
