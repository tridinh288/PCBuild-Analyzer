<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Support\Hardware\SpecFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A component with display-ready specs (spec section 27).
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->category->slug;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'category' => $category,
            'category_name' => $this->category->name,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'specs' => app(SpecFormatter::class)->format($category, $this->specs),
            // Cloudinary URLs are added with image storage in Phase 6; the frontend shows a
            // per-category placeholder while this is null.
            'image' => null,
        ];
    }
}
