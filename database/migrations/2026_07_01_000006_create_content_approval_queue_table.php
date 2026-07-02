<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_approval_queue')) {
            return;
        }

        Schema::create('content_approval_queue', function (Blueprint $table): void {
            $table->id();
            $table->string('approvable_type')->index();
            $table->unsignedBigInteger('approvable_id')->index();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->string('submitter_tier')->nullable();
            $table->string('content_title');
            $table->string('content_type_label');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_approval_queue');
    }
};
