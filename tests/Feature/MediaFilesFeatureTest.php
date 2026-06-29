<?php

namespace Tests\Feature;

use App\Jobs\ApplyMediaWatermarkJob;
use App\Jobs\ProcessMediaFileJob;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class MediaFilesFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_media_files_with_expected_structure(): void
    {
        $owner = User::factory()->professional()->create();
        $otherUser = User::factory()->professional()->create();
        $ownedMedia = $this->createMediaFile($owner, [
            'file_category' => 'image',
            'upload_context' => 'profile_photo',
            'is_orphan' => true,
        ]);
        $this->createMediaFile($otherUser, ['file_category' => 'image']);

        $this->actingAs($owner)
            ->getJson('/api/v1/media-files?orphans_only=1&file_category=image&upload_context=profile_photo')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'uploader_id',
                        'disk',
                        'path',
                        'original_name',
                        'mime_type',
                        'size',
                        'attachable_type',
                        'attachable_id',
                        'upload_context',
                        'file_category',
                        'sort_order',
                        'is_watermarked',
                        'watermarked_file_path',
                        'streaming_url',
                        'processing_status',
                        'processing_error',
                        'is_orphan',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownedMedia->id);
    }

    public function test_media_file_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/media-files')->assertUnauthorized();
    }

    public function test_user_cannot_access_another_users_media_file(): void
    {
        $owner = User::factory()->professional()->create();
        $otherUser = User::factory()->professional()->create();
        $mediaFile = $this->createMediaFile($owner);

        $this->actingAs($otherUser)
            ->getJson("/api/v1/media-files/{$mediaFile->id}")
            ->assertForbidden();
    }

    public function test_missing_media_file_returns_not_found(): void
    {
        $user = User::factory()->professional()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/media-files/999999')
            ->assertNotFound();
    }

    public function test_upload_stores_file_metadata_nullable_fields_and_model_casts(): void
    {
        Storage::fake('local');
        $user = User::factory()->professional()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/media-files', [
                'file' => UploadedFile::fake()->create('avatar.jpg', 1, 'image/jpeg'),
                'disk' => 'local',
                'directory' => 'profile-photos',
                'file_category' => 'image',
                'upload_context' => 'profile_photo',
                'sort_order' => 2,
                'is_orphan' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.uploader_id', $user->id)
            ->assertJsonPath('data.disk', 'local')
            ->assertJsonPath('data.original_name', 'avatar.jpg')
            ->assertJsonPath('data.file_category', 'image')
            ->assertJsonPath('data.upload_context', 'profile_photo')
            ->assertJsonPath('data.sort_order', 2)
            ->assertJsonPath('data.is_orphan', true)
            ->assertJsonPath('data.attachable_type', null)
            ->assertJsonPath('data.attachable_id', null);

        $mediaFile = MediaFile::query()->firstOrFail();

        Storage::disk('local')->assertExists($mediaFile->path);
        $this->assertIsInt($mediaFile->size);
        $this->assertIsInt($mediaFile->sort_order);
        $this->assertIsBool($mediaFile->is_orphan);
        $this->assertIsBool($mediaFile->is_watermarked);
    }

    public function test_upload_rejects_invalid_payload_fields(): void
    {
        $user = User::factory()->professional()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/media-files', [
                'file_category' => 'invalid',
                'upload_context' => 'invalid',
                'sort_order' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file', 'file_category', 'upload_context', 'sort_order']);
    }

    public function test_attach_validates_attachable_and_clears_orphan_flag(): void
    {
        $user = User::factory()->professional()->create();
        $mediaFile = $this->createMediaFile($user, ['is_orphan' => true]);

        $this->actingAs($user)
            ->postJson("/api/v1/media-files/{$mediaFile->id}/attach", [
                'attachable_type' => User::class,
                'attachable_id' => $user->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.attachable_type', User::class)
            ->assertJsonPath('data.attachable_id', $user->id)
            ->assertJsonPath('data.is_orphan', false);

        $this->actingAs($user)
            ->postJson("/api/v1/media-files/{$mediaFile->id}/attach", [
                'attachable_type' => User::class,
                'attachable_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attachable_id']);
    }

    public function test_mark_orphan_clears_attachable_metadata(): void
    {
        $user = User::factory()->professional()->create();
        $mediaFile = $this->createMediaFile($user, [
            'attachable_type' => User::class,
            'attachable_id' => $user->id,
            'is_orphan' => false,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/media-files/{$mediaFile->id}/orphan")
            ->assertOk()
            ->assertJsonPath('data.attachable_type', null)
            ->assertJsonPath('data.attachable_id', null)
            ->assertJsonPath('data.is_orphan', true);
    }

    public function test_database_constraints_reject_invalid_uploader_and_duplicate_path(): void
    {
        $user = User::factory()->professional()->create();
        $this->createMediaFile($user, ['path' => 'uploads/duplicate.jpg']);

        $this->expectException(QueryException::class);
        $this->createMediaFile($user, ['path' => 'uploads/duplicate.jpg']);
    }

    public function test_database_foreign_key_rejects_missing_uploader(): void
    {
        $this->expectException(QueryException::class);

        MediaFile::query()->create($this->mediaAttributes([
            'uploader_id' => 999999,
            'path' => 'uploads/missing-uploader.jpg',
        ]));
    }

    public function test_processing_jobs_update_status_metadata_and_failures(): void
    {
        $user = User::factory()->professional()->create();
        $mediaFile = $this->createMediaFile($user, [
            'path' => 'uploads/source.jpg',
            'processing_status' => 'pending',
            'processing_error' => 'old error',
        ]);

        (new ProcessMediaFileJob($mediaFile, 'Extracted text'))->handle();

        $mediaFile->refresh();
        $this->assertSame('processed', $mediaFile->processing_status);
        $this->assertSame('Extracted text', $mediaFile->extracted_text);
        $this->assertNull($mediaFile->processing_error);

        (new ApplyMediaWatermarkJob($mediaFile))->handle();

        $mediaFile->refresh();
        $this->assertTrue($mediaFile->is_watermarked);
        $this->assertSame('watermarked/uploads/source.jpg', $mediaFile->watermarked_file_path);
        $this->assertSame('processed', $mediaFile->processing_status);

        (new ProcessMediaFileJob($mediaFile))->failed(new RuntimeException('processor unavailable'));

        $mediaFile->refresh();
        $this->assertSame('failed', $mediaFile->processing_status);
        $this->assertSame('processor unavailable', $mediaFile->processing_error);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createMediaFile(User $uploader, array $overrides = []): MediaFile
    {
        return MediaFile::query()->create($this->mediaAttributes([
            'uploader_id' => $uploader->id,
            ...$overrides,
        ]));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function mediaAttributes(array $overrides = []): array
    {
        return [
            'uploader_id' => User::factory(),
            'disk' => 'local',
            'path' => 'uploads/'.uniqid('media_', true).'.jpg',
            'original_name' => 'media.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'attachable_type' => null,
            'attachable_id' => null,
            'upload_context' => null,
            'file_category' => null,
            'sort_order' => 0,
            'is_watermarked' => false,
            'watermarked_file_path' => null,
            'streaming_url' => null,
            'extracted_text' => null,
            'processing_status' => null,
            'processing_error' => null,
            'is_orphan' => true,
            ...$overrides,
        ];
    }
}
