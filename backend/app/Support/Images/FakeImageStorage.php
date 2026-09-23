<?php

namespace App\Support\Images;

use Illuminate\Http\UploadedFile;

/**
 * In-memory ImageStorage for tests: no network calls (D-022). Records what happened so tests
 * can assert uploads and deletions.
 */
class FakeImageStorage implements ImageStorage
{
    /** @var list<array{folder: string, name: string, public_id: string}> */
    public array $uploaded = [];

    /** @var list<string> */
    public array $deleted = [];

    public bool $failUploads = false;

    /** Succeed this many uploads, then fail: an outage or a revoked credential mid-run. */
    public ?int $failAfter = null;

    public function upload(UploadedFile $file, string $folder): string
    {
        if ($this->failUploads || ($this->failAfter !== null && count($this->uploaded) >= $this->failAfter)) {
            throw new ImageStorageException('Không tải được ảnh lên, vui lòng thử lại.');
        }

        $publicId = $folder.'/fake-'.(count($this->uploaded) + 1);
        $this->uploaded[] = ['folder' => $folder, 'name' => $file->getClientOriginalName(), 'public_id' => $publicId];

        return $publicId;
    }

    public function delete(string $publicId): void
    {
        $this->deleted[] = $publicId;
    }

    public function url(?string $publicId, string $preset): ?string
    {
        return $publicId === null ? null : "https://images.test/{$preset}/{$publicId}";
    }
}
