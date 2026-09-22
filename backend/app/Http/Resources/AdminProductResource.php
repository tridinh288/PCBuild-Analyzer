<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Admin view of a product: the public fields plus raw specs (for editing) and usage.
 *
 * @mixin Product
 */
class AdminProductResource extends ProductResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'raw_specs' => (object) $this->specs,
            'image_public_id' => $this->image_public_id,
            'used_in_builds' => $this->whenCounted('buildItems', fn () => $this->build_items_count, fn () => $this->buildItems()->count()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
