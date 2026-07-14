<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('library_categories')) {
            Schema::create('library_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('parent_id')->references('id')->on('library_categories')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('library_items')) {
            Schema::create('library_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('category_id')->constrained('library_categories');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary')->nullable();
                $table->longText('content')->nullable();
                $table->foreignId('plant_type_id')->nullable()->constrained('plant_types')->nullOnDelete();
                $table->string('author')->nullable();
                $table->string('source')->nullable();
                $table->unsignedSmallInteger('published_year')->nullable();
                $table->enum('access_level', ['public', 'professional_only', 'partner_only', 'gold', 'diamond', 'platinum'])->default('professional_only');
                $table->boolean('download_allowed')->default(false);
                $table->boolean('copy_paste_disabled')->default(false);
                $table->integer('download_count')->default(0);
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->boolean('is_ai_trainable')->default(true);
                $table->enum('content_type', ['article', 'video', 'document', 'presentation', 'case_study', 'safety_bulletin']);
                $table->string('item_type')->nullable();
                $table->integer('view_count')->default(0);
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->foreignId('file_media_id')->nullable()->constrained('media_files')->nullOnDelete();
                $table->timestamps();

                $table->index(['category_id', 'status']);
                $table->index(['plant_type_id', 'status']);
                $table->index('access_level');
            });
        }

        if (! Schema::hasTable('library_access_logs')) {
            Schema::create('library_access_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('library_item_id')->constrained('library_items')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('action', ['view', 'download']);
                $table->string('ip_address');
                $table->timestamp('created_at')->index();

                $table->index(['library_item_id', 'action']);
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('library_access_rules')) {
            Schema::create('library_access_rules', function (Blueprint $table): void {
                $table->id();
                $table->enum('partner_tier', ['gold', 'diamond', 'platinum'])->unique();
                $table->boolean('can_view')->default(true);
                $table->boolean('can_download')->default(false);
                $table->boolean('can_copy_paste')->default(false);
                $table->boolean('requires_watermark')->default(true);
                $table->integer('max_downloads_per_month')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('knowledge_domains')) {
            Schema::create('knowledge_domains', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->foreignId('plant_type_id')->nullable()->constrained('plant_types')->nullOnDelete();
                $table->string('icon')->nullable();
                $table->integer('total_question_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('name');
                $table->index(['plant_type_id', 'is_active']);
            });
        } else {
            Schema::table('knowledge_domains', function (Blueprint $table): void {
                if (! Schema::hasColumn('knowledge_domains', 'plant_type_id')) {
                    $table->foreignId('plant_type_id')->nullable()->after('description')->constrained('plant_types')->nullOnDelete();
                }

                if (! Schema::hasColumn('knowledge_domains', 'icon')) {
                    $table->string('icon')->nullable()->after('plant_type_id');
                }

                if (! Schema::hasColumn('knowledge_domains', 'total_question_count')) {
                    $table->integer('total_question_count')->default(0)->after('icon');
                }

                if (! Schema::hasColumn('knowledge_domains', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('total_question_count');
                }

                if (! Schema::hasColumn('knowledge_domains', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('is_active');
                }
            });
        }

        if (! Schema::hasTable('quiz_questions')) {
            Schema::create('quiz_questions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('knowledge_domain_id')->constrained('knowledge_domains')->cascadeOnDelete();
                $table->text('question_text');
                $table->foreignId('question_image_media_id')->nullable()->constrained('media_files')->nullOnDelete();
                $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
                $table->enum('status', ['active', 'draft', 'archived'])->default('active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['knowledge_domain_id', 'status']);
                $table->index('difficulty_level');
            });
        } else {
            Schema::table('quiz_questions', function (Blueprint $table): void {
                if (! Schema::hasColumn('quiz_questions', 'knowledge_domain_id')) {
                    $table->foreignId('knowledge_domain_id')->after('id')->constrained('knowledge_domains')->cascadeOnDelete();
                }

                if (! Schema::hasColumn('quiz_questions', 'question_image_media_id')) {
                    $table->foreignId('question_image_media_id')->nullable()->after('question_text')->constrained('media_files')->nullOnDelete();
                }

                if (! Schema::hasColumn('quiz_questions', 'difficulty_level')) {
                    $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium')->after('question_image_media_id');
                }

                if (! Schema::hasColumn('quiz_questions', 'status')) {
                    $table->enum('status', ['active', 'draft', 'archived'])->default('active')->after('difficulty_level');
                }

                if (! Schema::hasColumn('quiz_questions', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn('quiz_questions', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('quiz_question_choices')) {
            Schema::create('quiz_question_choices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('question_id')->constrained('quiz_questions')->cascadeOnDelete();
                $table->text('choice_text');
                $table->boolean('is_correct')->default(false);
                $table->text('explanation');
                $table->integer('sort_order')->default(0);

                $table->index(['question_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('knowledge_domain_id')->constrained('knowledge_domains');
                $table->integer('total_questions')->default(50);
                $table->integer('correct_count')->default(0);
                $table->decimal('score_percentage', 5, 2);
                $table->decimal('pass_threshold', 5, 2)->default(80.00);
                $table->boolean('is_passed')->default(false);
                $table->timestamp('started_at');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('next_attempt_allowed_at')->nullable();
                $table->boolean('counted_for_rank_promotion')->default(false);
                $table->timestamps();

                $table->index(['user_id', 'knowledge_domain_id']);
                $table->index(['knowledge_domain_id', 'is_passed']);
            });
        } else {
            Schema::table('quiz_attempts', function (Blueprint $table): void {
                if (! Schema::hasColumn('quiz_attempts', 'knowledge_domain_id')) {
                    $table->foreignId('knowledge_domain_id')->after('user_id')->constrained('knowledge_domains');
                }

                if (! Schema::hasColumn('quiz_attempts', 'total_questions')) {
                    $table->integer('total_questions')->default(50)->after('knowledge_domain_id');
                }

                if (! Schema::hasColumn('quiz_attempts', 'correct_count')) {
                    $table->integer('correct_count')->default(0)->after('total_questions');
                }

                if (! Schema::hasColumn('quiz_attempts', 'score_percentage')) {
                    $table->decimal('score_percentage', 5, 2)->default(0)->after('correct_count');
                }

                if (! Schema::hasColumn('quiz_attempts', 'pass_threshold')) {
                    $table->decimal('pass_threshold', 5, 2)->default(80.00)->after('score_percentage');
                }

                if (! Schema::hasColumn('quiz_attempts', 'is_passed')) {
                    $table->boolean('is_passed')->default(false)->after('pass_threshold');
                }

                if (! Schema::hasColumn('quiz_attempts', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable()->after('started_at');
                }

                if (! Schema::hasColumn('quiz_attempts', 'next_attempt_allowed_at')) {
                    $table->timestamp('next_attempt_allowed_at')->nullable()->after('submitted_at');
                }

                if (! Schema::hasColumn('quiz_attempts', 'counted_for_rank_promotion')) {
                    $table->boolean('counted_for_rank_promotion')->default(false)->after('next_attempt_allowed_at');
                }
            });
        }

        if (! Schema::hasTable('quiz_attempt_questions')) {
            Schema::create('quiz_attempt_questions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('quiz_attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
                $table->foreignId('question_id')->constrained('quiz_questions');
                $table->foreignId('selected_choice_id')->nullable()->constrained('quiz_question_choices')->nullOnDelete();
                $table->boolean('is_correct')->default(false);
                $table->integer('sort_order')->default(0);

                $table->index(['quiz_attempt_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('user_domain_expertise')) {
            Schema::create('user_domain_expertise', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('knowledge_domain_id')->constrained('knowledge_domains');
                $table->decimal('self_rated_percentage', 5, 2)->default(0);
                $table->boolean('is_quiz_unlocked')->default(false);
                $table->timestamp('unlocked_at')->nullable();
                $table->foreignId('unlocked_via_attempt_id')->nullable()->constrained('quiz_attempts')->nullOnDelete();
                $table->boolean('is_top_5_displayed')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'knowledge_domain_id']);
                $table->index(['knowledge_domain_id', 'is_top_5_displayed']);
            });
        }

        if (! Schema::hasTable('expertise_rank_tiers')) {
            Schema::create('expertise_rank_tiers', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->integer('min_years_experience')->nullable();
                $table->decimal('default_cap_percentage', 5, 2);
                $table->integer('rank_order')->unique();
                $table->integer('required_quiz_count')->default(10);
                $table->integer('required_mandatory_quiz_count')->default(3);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_expertise_ranks')) {
            Schema::create('user_expertise_ranks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('rank_tier_id')->constrained('expertise_rank_tiers');
                $table->enum('promotion_source', ['admin_manual_review', 'quiz_pathway']);
                $table->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('promotion_note')->nullable();
                $table->timestamp('effective_at');
                $table->boolean('is_current')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'is_current']);
                $table->index('rank_tier_id');
            });
        }

        if (! Schema::hasTable('mandatory_quiz_domains')) {
            Schema::create('mandatory_quiz_domains', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('plant_type_id')->constrained('plant_types');
                $table->foreignId('knowledge_domain_id')->constrained('knowledge_domains');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['plant_type_id', 'knowledge_domain_id']);
                $table->index(['knowledge_domain_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('rank_promotion_quiz_logs')) {
            Schema::create('rank_promotion_quiz_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('quiz_attempt_id')->constrained('quiz_attempts');
                $table->foreignId('knowledge_domain_id')->constrained('knowledge_domains');
                $table->boolean('is_mandatory')->default(false);
                $table->integer('promotion_cycle_no');
                $table->foreignId('resulted_promotion_id')->nullable()->constrained('user_expertise_ranks')->nullOnDelete();
                $table->timestamp('created_at')->index();

                $table->index(['user_id', 'promotion_cycle_no']);
                $table->index(['knowledge_domain_id', 'is_mandatory']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_promotion_quiz_logs');
        Schema::dropIfExists('mandatory_quiz_domains');
        Schema::dropIfExists('user_expertise_ranks');
        Schema::dropIfExists('expertise_rank_tiers');
        Schema::dropIfExists('user_domain_expertise');
        Schema::dropIfExists('quiz_attempt_questions');
        Schema::dropIfExists('quiz_question_choices');
        Schema::dropIfExists('library_access_rules');
        Schema::dropIfExists('library_access_logs');
        Schema::dropIfExists('library_items');
        Schema::dropIfExists('library_categories');

        if (Schema::hasTable('quiz_attempts')) {
            Schema::table('quiz_attempts', function (Blueprint $table): void {
                foreach (['knowledge_domain_id'] as $column) {
                    if (Schema::hasColumn('quiz_attempts', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }

                $columns = [
                    'total_questions',
                    'correct_count',
                    'score_percentage',
                    'pass_threshold',
                    'is_passed',
                    'submitted_at',
                    'next_attempt_allowed_at',
                    'counted_for_rank_promotion',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('quiz_attempts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('quiz_questions')) {
            Schema::table('quiz_questions', function (Blueprint $table): void {
                foreach (['knowledge_domain_id', 'question_image_media_id', 'created_by', 'updated_by'] as $column) {
                    if (Schema::hasColumn('quiz_questions', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }

                foreach (['difficulty_level', 'status'] as $column) {
                    if (Schema::hasColumn('quiz_questions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('knowledge_domains')) {
            Schema::table('knowledge_domains', function (Blueprint $table): void {
                if (Schema::hasColumn('knowledge_domains', 'plant_type_id')) {
                    $table->dropConstrainedForeignId('plant_type_id');
                }

                foreach (['icon', 'total_question_count', 'is_active', 'sort_order'] as $column) {
                    if (Schema::hasColumn('knowledge_domains', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
