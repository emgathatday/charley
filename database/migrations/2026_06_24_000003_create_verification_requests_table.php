<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('verification_requests')) {
            Schema::create('verification_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('submission_type', ['initial', 'renewal', 'resubmission']);
                $table->enum('verification_method', ['work_email', 'linkedin', 'company_letter', 'university_letter', 'justification_letter']);
                $table->json('document_media_ids')->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'more_info_required'])->default('pending');
                $table->text('admin_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};
