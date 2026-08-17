<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('weekly_themes')) {
            Schema::create('weekly_themes', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('week_start_date');
                $table->date('week_end_date');
                $table->foreignId('created_by_admin_id');
                $table->enum('status', ['active', 'archived']);
                $table->timestamps();

                $table->index('status');
                $table->index(['week_start_date', 'week_end_date']);
                $table->foreign('created_by_admin_id')->references('id')->on('users');
            });
        }

        if (! Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('posted_by_admin_id')->nullable();
                $table->foreignId('on_behalf_of_partner_id')->nullable();
                $table->foreignId('weekly_theme_id')->nullable();
                $table->foreignId('plant_type_id')->nullable();
                $table->string('title');
                $table->longText('body');
                $table->boolean('is_anonymous')->default(false);
                $table->enum('status', ['pending', 'published', 'hidden', 'flagged']);
                $table->json('attachment_media_ids')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('is_anonymous');
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('posted_by_admin_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('on_behalf_of_partner_id')->references('id')->on('partner_profiles')->nullOnDelete();
                $table->foreign('weekly_theme_id')->references('id')->on('weekly_themes')->nullOnDelete();
                $table->foreign('plant_type_id')->references('id')->on('plant_types')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('answers')) {
            Schema::create('answers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('question_id');
                $table->foreignId('user_id');
                $table->boolean('is_anonymous')->default(false);
                $table->longText('body');
                $table->boolean('is_admin_featured')->default(false);
                $table->enum('confidence_level', ['low', 'medium', 'high'])->nullable();
                $table->integer('admin_rank_order')->nullable();
                $table->json('attachment_media_ids')->nullable();
                $table->timestamps();

                $table->index('is_admin_featured');
                $table->index('admin_rank_order');
                $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        if (! Schema::hasTable('question_domain_links')) {
            Schema::create('question_domain_links', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('question_id');
                $table->foreignId('knowledge_domain_id');

                $table->unique(['question_id', 'knowledge_domain_id']);
                $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
                $table->foreign('knowledge_domain_id')->references('id')->on('knowledge_domains');
            });
        }

        if (! Schema::hasTable('point_transactions')) {
            Schema::create('point_transactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->integer('points');
                $table->enum('source_type', ['question', 'answer', 'manual_adjustment']);
                $table->unsignedBigInteger('source_id')->nullable();
                $table->text('reason')->nullable();
                $table->foreignId('performed_by')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['source_type', 'source_id']);
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('user_reputation')) {
            Schema::create('user_reputation', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->unique();
                $table->integer('total_points')->default(0);
                $table->tinyInteger('current_star_rank')->default(1);
                $table->timestamp('updated_at')->nullable();

                $table->index('current_star_rank');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        if (! Schema::hasTable('reputation_rank_tiers')) {
            Schema::create('reputation_rank_tiers', function (Blueprint $table): void {
                $table->id();
                $table->tinyInteger('star_level');
                $table->integer('min_points');
                $table->string('label')->nullable();

                $table->unique('star_level');
                $table->index('min_points');
            });
        }

        if (! Schema::hasTable('leaderboard_settings')) {
            Schema::create('leaderboard_settings', function (Blueprint $table): void {
                $table->id();
                $table->integer('min_points_threshold');
                $table->integer('top_n')->default(10);
                $table->date('effective_from');

                $table->index('effective_from');
            });
        }

        if (! Schema::hasTable('monthly_leaderboard_snapshots')) {
            Schema::create('monthly_leaderboard_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->string('year_month');
                $table->integer('total_points_in_month');
                $table->integer('rank_position');
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['year_month', 'rank_position']);
                $table->index(['year_month', 'user_id']);
                $table->foreign('user_id')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_leaderboard_snapshots');
        Schema::dropIfExists('leaderboard_settings');
        Schema::dropIfExists('reputation_rank_tiers');
        Schema::dropIfExists('user_reputation');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('question_domain_links');
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('weekly_themes');
    }
};
