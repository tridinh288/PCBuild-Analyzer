<?php

namespace App\Http\Resources;

use App\Domain\Configuration\SlotRules;
use App\Models\Category;
use App\Support\Hardware\SpecSchema;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $slots = app(SlotRules::class);
        $slot = app(SpecSchema::class)->slots()[$this->slug];

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'products_count' => $this->whenCounted('products'),
            // Builder slot behaviour (config/hardware.php `slots`)
            'slot' => [
                'required' => $slot['required'], // true, or a condition such as 'unless_cpu_has_integrated_graphics'
                'multiple' => $slots->acceptsSeveralProducts($this->slug),
                'accepts_quantity' => $slots->acceptsQuantity($this->slug),
                'max_quantity' => $slots->maxQuantity($this->slug),
            ],
        ];
    }
}
