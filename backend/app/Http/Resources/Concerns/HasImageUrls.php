<?php

namespace App\Http\Resources\Concerns;

use App\Support\Images\ImageStorage;

trait HasImageUrls
{
    /**
     * { thumb, large } URLs built from the stored public_id, or null (frontend placeholder).
     *
     * @return array{thumb: ?string, large: ?string}|null
     */
    protected function imageUrls(?string $publicId): ?array
    {
        if ($publicId === null) {
            return null;
        }

        $images = app(ImageStorage::class);

        return ['thumb' => $images->url($publicId, 'thumb'), 'large' => $images->url($publicId, 'large')];
    }
}
