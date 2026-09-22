<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\HasImageUrls;
use App\Models\Build;
use App\Models\BuildItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A template build. `items` only when loaded (detail page); `total_price` comes from the
 * withTotalPrice() scope (D-032).
 *
 * @mixin Build
 */
class BuildResource extends JsonResource
{
    use HasImageUrls;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'purpose' => $this->purpose->value,
            'purpose_label' => $this->purpose->label(),
            'is_featured' => $this->is_featured,
            'total_price' => (int) $this->total_price,
            'image' => $this->imageUrls($this->image_public_id),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (BuildItem $item) => [
                'category' => $item->product->category->slug,
                'quantity' => $item->quantity,
                'product' => new ProductResource($item->product),
            ])->all()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
