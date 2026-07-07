<?php

namespace Tests\Unit;

use App\Events\LibraryAccessLogged;
use App\Events\LibraryAiTrainableContentReady;
use App\Events\LibraryUploadProcessingRequested;
use App\Events\LibraryWatermarkPreparationRequested;
use App\Jobs\AggregateLibraryAccessLogJob;
use App\Jobs\ApplyMediaWatermarkJob;
use App\Jobs\ExtractAiTrainableLibraryContentJob;
use App\Jobs\PrepareLibraryWatermarkJob;
use App\Jobs\ProcessLibraryUploadJob;
use App\Jobs\ProcessMediaFileJob;
use App\Jobs\RefreshLibraryDownloadCountJob;
use App\Models\LibraryAccessLog;
use App\Models\LibraryAccessRule;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\MediaFile;
use App\Models\User;
use App\Services\Library\LibraryAccessService;
use App\Services\Library\LibraryContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LibraryServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_service_creates_updates_approves_archives_and_filters_trainable_items(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $service = new LibraryContentService;

        $item = $service->create([
            'category_id' => $category->id,
            'title' => 'Pump Safety Guide',
            'content' => 'Pump safety content',
            'content_type' => 'document',
            'access_level' => 'professional_only',
            'download_allowed' => true,
            'is_ai_trainable' => true,
        ], $admin);

        $this->assertSame('pump-safety-guide', $item->slug);
        $this->assertSame(LibraryItem::STATUS_DRAFT, $item->status);

        $updated = $service->update($item, ['title' => 'Pump Safety Guide Updated']);
        $this->assertSame('pump-safety-guide-updated', $updated->slug);

        $approved = $service->approve($updated, $admin);
        $this->assertSame(LibraryItem::STATUS_PUBLISHED, $approved->status);
        $this->assertSame($admin->id, $approved->approved_by);
        $this->assertCount(1, $service->aiTrainableContent());

        $archived = $service->archive($approved);
        $this->assertSame(LibraryItem::STATUS_ARCHIVED, $archived->status);
    }

    public function test_content_service_rejects_empty_slug_and_raw_file_paths(): void
    {
        $service = new LibraryContentService;

        try {
            $service->create([
                'category_id' => $this->createCategory()->id,
                'title' => '***',
                'content_type' => 'article',
            ]);
            $this->fail('Expected empty slug validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('slug', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $service->create([
            'category_id' => $this->createCategory(['slug' => 'second-category'])->id,
            'title' => 'Raw File Path',
            'content_type' => 'article',
            'file_path' => '/tmp/raw.pdf',
        ]);
    }

    public function test_access_service_checks_tiers_downloads_watermarks_copy_and_logs(): void
    {
        $service = new LibraryAccessService;
        $category = $this->createCategory();
        $partner = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $admin = User::factory()->admin()->create();
        $media = $this->createMediaFile($admin);
        $item = $this->createItem($category, [
            'access_level' => 'partner_only',
            'download_allowed' => true,
            'copy_paste_disabled' => false,
            'file_media_id' => $media->id,
        ]);

        LibraryAccessRule::query()->create([
            'partner_tier' => 'gold',
            'can_view' => true,
            'can_download' => true,
            'can_copy_paste' => false,
            'requires_watermark' => true,
            'max_downloads_per_month' => 5,
        ]);

        $this->assertTrue($service->canView($item, $partner, 'gold'));
        $this->assertTrue($service->canDownload($item, $partner, 'gold'));
        $this->assertTrue($service->requiresWatermark($item, 'gold'));
        $this->assertFalse($service->canCopyPaste($item, $partner, 'gold'));

        $view = $service->recordAccess($item, $partner, LibraryAccessLog::ACTION_VIEW, '127.0.0.1');
        $download = $service->recordAccess($item, $partner, LibraryAccessLog::ACTION_DOWNLOAD, '127.0.0.1');

        $this->assertSame(LibraryAccessLog::ACTION_VIEW, $view->action);
        $this->assertSame(LibraryAccessLog::ACTION_DOWNLOAD, $download->action);
        $this->assertDatabaseHas('library_items', ['id' => $item->id, 'view_count' => 1, 'download_count' => 1]);
    }

    public function test_access_service_rejects_unavailable_actions_and_download_disabled_items(): void
    {
        $service = new LibraryAccessService;
        $category = $this->createCategory();
        $user = User::factory()->professional()->create();
        $item = $this->createItem($category, ['download_allowed' => false]);

        $this->assertFalse($service->canDownload($item, $user));

        try {
            $service->assertCanDownload($item, $user);
            $this->fail('Expected download validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('library_item', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $service->recordAccess($item, $user, 'print', '127.0.0.1');
    }

    public function test_library_jobs_dispatch_processing_watermark_and_ai_work(): void
    {
        Queue::fake();
        Event::fake();
        $admin = User::factory()->admin()->create();
        $media = $this->createMediaFile($admin, ['extracted_text' => 'Extracted library content']);
        $item = $this->createItem($this->createCategory(), [
            'download_allowed' => true,
            'is_ai_trainable' => true,
            'file_media_id' => $media->id,
        ]);

        (new ProcessLibraryUploadJob($item))->handle();

        Event::assertDispatched(LibraryUploadProcessingRequested::class);
        Queue::assertPushed(ProcessMediaFileJob::class);
        Queue::assertPushed(PrepareLibraryWatermarkJob::class);
        Queue::assertPushed(ExtractAiTrainableLibraryContentJob::class);

        (new PrepareLibraryWatermarkJob($item))->handle(new LibraryAccessService);

        Event::assertDispatched(LibraryWatermarkPreparationRequested::class);
        Queue::assertPushed(ApplyMediaWatermarkJob::class);
    }

    public function test_watermark_ai_and_access_aggregate_jobs_update_records(): void
    {
        Queue::fake();
        Event::fake();
        $admin = User::factory()->admin()->create();
        $media = $this->createMediaFile($admin, ['extracted_text' => 'Extracted training text']);
        $item = $this->createItem($this->createCategory(), [
            'content' => null,
            'download_allowed' => true,
            'is_ai_trainable' => true,
            'file_media_id' => $media->id,
        ]);

        (new ApplyMediaWatermarkJob($media, 'library/watermarked/guide.pdf'))->handle();

        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'is_watermarked' => true,
            'watermarked_file_path' => 'library/watermarked/guide.pdf',
            'processing_status' => 'processed',
        ]);

        (new ExtractAiTrainableLibraryContentJob($item))->handle();

        Event::assertDispatched(LibraryAiTrainableContentReady::class);
        $this->assertDatabaseHas('library_items', ['id' => $item->id, 'content' => 'Extracted training text']);

        $view = LibraryAccessLog::query()->create([
            'library_item_id' => $item->id,
            'user_id' => $admin->id,
            'action' => LibraryAccessLog::ACTION_VIEW,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
        LibraryAccessLog::query()->create([
            'library_item_id' => $item->id,
            'user_id' => $admin->id,
            'action' => LibraryAccessLog::ACTION_DOWNLOAD,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        (new AggregateLibraryAccessLogJob($view))->handle();
        Queue::assertPushed(RefreshLibraryDownloadCountJob::class);
        Event::assertDispatched(LibraryAccessLogged::class);

        (new RefreshLibraryDownloadCountJob($item))->handle();
        $this->assertDatabaseHas('library_items', ['id' => $item->id, 'view_count' => 1, 'download_count' => 1]);
    }

    private function createCategory(array $attributes = []): LibraryCategory
    {
        return LibraryCategory::query()->create(array_merge([
            'title' => 'Library Category',
            'slug' => 'library-category',
            'parent_id' => null,
            'sort_order' => 10,
        ], $attributes));
    }

    private function createItem(LibraryCategory $category, array $attributes = []): LibraryItem
    {
        $approver = User::factory()->admin()->create();

        return LibraryItem::query()->create(array_merge([
            'category_id' => $category->id,
            'user_id' => $approver->id,
            'title' => 'Library Item',
            'slug' => 'library-item',
            'summary' => 'Library summary',
            'content' => 'Library content',
            'access_level' => 'public',
            'download_allowed' => false,
            'copy_paste_disabled' => false,
            'download_count' => 0,
            'status' => LibraryItem::STATUS_PUBLISHED,
            'is_ai_trainable' => true,
            'content_type' => 'article',
            'item_type' => 'article',
            'view_count' => 0,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'year' => 2026,
            'file_media_id' => null,
        ], $attributes));
    }

    private function createMediaFile(User $user, array $attributes = []): MediaFile
    {
        return MediaFile::query()->create(array_merge([
            'uploader_id' => $user->id,
            'disk' => 'local',
            'path' => 'library/guide.pdf',
            'original_name' => 'guide.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'upload_context' => 'library_item',
            'file_category' => 'document',
            'processing_status' => 'processed',
            'is_orphan' => false,
        ], $attributes));
    }
}
