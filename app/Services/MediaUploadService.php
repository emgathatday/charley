<?php

namespace App\Services;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MediaUploadService
{
    public function __construct(
        private readonly FilesystemFactory $filesystem,
    ) {
    }

    /**
     * @param array{
     *     disk?: string,
     *     directory?: string,
     *     file_category?: string|null,
     *     upload_context?: string|null,
     *     attachable?: Model|null,
     *     sort_order?: int,
     *     is_orphan?: bool
     * } $options
     */
    public function storeUpload(UploadedFile $file, User $uploader, array $options = []): MediaFile
    {
        $disk = $options['disk'] ?? 's3';
        $path = $this->uniquePath($file, $options['directory'] ?? 'uploads');

        $this->filesystem->disk($disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        try {
            return DB::transaction(function () use ($file, $uploader, $options, $disk, $path): MediaFile {
                return $this->createMetadata([
                    'uploader_id' => $uploader->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'file_category' => $options['file_category'] ?? null,
                    'upload_context' => $options['upload_context'] ?? null,
                    'sort_order' => $options['sort_order'] ?? 0,
                    'is_orphan' => $options['is_orphan'] ?? ! isset($options['attachable']),
                    ...$this->attachableFields($options['attachable'] ?? null),
                ]);
            });
        } catch (Throwable $exception) {
            $this->filesystem->disk($disk)->delete($path);

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function createMetadata(array $attributes): MediaFile
    {
        foreach (['uploader_id', 'disk', 'path', 'original_name', 'mime_type', 'size'] as $field) {
            if (! array_key_exists($field, $attributes) || $attributes[$field] === null || $attributes[$field] === '') {
                throw new RuntimeException("Media metadata field [{$field}] is required.");
            }
        }

        return MediaFile::query()->create($attributes);
    }

    public function attach(MediaFile $mediaFile, Model $attachable): MediaFile
    {
        return DB::transaction(function () use ($mediaFile, $attachable): MediaFile {
            $mediaFile->fill([
                ...$this->attachableFields($attachable),
                'is_orphan' => false,
            ]);

            $mediaFile->save();

            return $mediaFile;
        });
    }

    private function uniquePath(UploadedFile $file, string $directory): string
    {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $directory = trim($directory, '/');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $path = $directory.'/'.Str::uuid()->toString().'.'.$extension;

            if (! MediaFile::query()->where('path', $path)->exists()) {
                return $path;
            }
        }

        throw new RuntimeException('Unable to generate a unique media file path.');
    }

    /**
     * @return array{attachable_type: string|null, attachable_id: int|null}
     */
    private function attachableFields(?Model $attachable): array
    {
        if ($attachable === null) {
            return [
                'attachable_type' => null,
                'attachable_id' => null,
            ];
        }

        return [
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
        ];
    }
}
