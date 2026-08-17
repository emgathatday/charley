<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qa_moderation_rules')) {
            Schema::create('qa_moderation_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->enum('rule_type', ['keyword', 'max_links', 'min_length', 'regex', 'attachment_type', 'custom']);
                $table->enum('target_type', ['question', 'answer', 'both']);
                $table->json('config');
                $table->enum('severity', ['low', 'medium', 'high']);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'target_type'], 'qa_mod_rules_active_target_idx');
                $table->index(['rule_type', 'is_active'], 'qa_mod_rules_type_active_idx');
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('qa_moderation_warnings')) {
            Schema::create('qa_moderation_warnings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->string('warnable_type');
                $table->unsignedBigInteger('warnable_id');
                $table->enum('source', ['system_rule', 'ai', 'admin']);
                $table->enum('severity', ['low', 'medium', 'high']);
                $table->text('reason');
                $table->json('evidence')->nullable();
                $table->enum('status', ['pending_review', 'safe', 'confirmed', 'dismissed']);
                $table->foreignId('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['warnable_type', 'warnable_id'], 'qa_mod_warnings_warnable_idx');
                $table->index(['status', 'created_at'], 'qa_mod_warnings_status_created_idx');
                $table->index(['user_id', 'status'], 'qa_mod_warnings_user_status_idx');
                $table->index(['source', 'severity'], 'qa_mod_warnings_source_severity_idx');
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('qa_user_warning_summaries')) {
            Schema::create('qa_user_warning_summaries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->unique();
                $table->integer('confirmed_warning_count')->default(0);
                $table->timestamp('last_warning_at')->nullable();
                $table->boolean('is_frozen')->default(false);
                $table->timestamp('frozen_at')->nullable();
                $table->text('frozen_reason')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index(['user_id', 'is_frozen'], 'qa_warning_summaries_user_frozen_idx');
                $table->index(['is_frozen', 'confirmed_warning_count'], 'qa_warning_summaries_frozen_count_idx');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_user_warning_summaries');
        Schema::dropIfExists('qa_moderation_warnings');
        Schema::dropIfExists('qa_moderation_rules');
    }
};
