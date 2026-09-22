<?php

namespace App\Support\Images;

use Illuminate\Http\UploadedFile;

/**
 * Where product and build images live (D-022). The database stores only the returned
 * public_id; URLs are built per display preset. Tests bind FakeImageStorage.
 */
interface ImageStorage
{
    /**
     * @return string The public_id of the stored image
     *
     * @throws ImageStorageException when the upload fails
     */
    public function upload(UploadedFile $file, string $folder): string;

    public function delete(string $publicId): void;

    /**
     * URL for a preset from config/images.php ('thumb', 'large'); null when there is no image,
     * so the frontend shows its per-category placeholder.
     */
    public function url(?string $publicId, string $preset): ?string;
}
