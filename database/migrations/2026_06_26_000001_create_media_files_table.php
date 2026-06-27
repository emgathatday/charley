<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media_files')) {
            return;
        }

        Schema::create('media_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('uploader_id');
            $table->string('disk')->default('s3');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('attachable_type')->nullable();
            $table->unsignedBigInteger('attachable_id')->nullable();
            $table->enum('upload_context', [
                'profile_photo',
                'verification_document',
                'library_item',
                'event_thumbnail',
                'post_attachment',
                'question_attachment',
                'answer_attachment',
                'partner_asset',
                'service_asset',
                'general',
            ])->nullable();
            $table->enum('file_category', [
                'image',
                'document',
                'process_diagram',
                'video',
                'presentation',
                'audio',
                'archive',
                'other',
            ])->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_watermarked')->default(false);
            $table->string('watermarked_file_path')->nullable();
            $table->string('streaming_url')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->enum('processing_status', [
                'pending',
                'processing',
                'processed',
                'failed',
            ])->nullable();
            $table->text('processing_error')->nullable();
            $table->boolean('is_orphan')->default(false);
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
            $table->foreign('uploader_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
