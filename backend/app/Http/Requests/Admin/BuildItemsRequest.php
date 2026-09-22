<?php

namespace App\Http\Requests\Admin;

use App\Domain\Configuration\SlotRules;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * PUT /admin/builds/{id}/items — the full component list of a template.
 *
 * Checks slot structure only (one CPU, quantity limits…). Hardware compatibility is NOT
 * enforced here: the engine reports it, like in the public Builder (D-018).
 */
class BuildItemsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'items' => ['present', 'array', 'max:20'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $slots = $this->container->make(SlotRules::class);
            $items = collect($this->input('items'));
            $categories = Product::with('category')->findMany($items->pluck('product_id'))
                ->mapWithKeys(fn (Product $product) => [$product->id => $product->category->slug]);

            $byCategory = $items->groupBy(fn (array $item) => $categories[$item['product_id']]);

            foreach ($byCategory as $category => $categoryItems) {
                $quantity = $categoryItems->sum('quantity');

                if (! $slots->acceptsSeveralProducts($category) && $categoryItems->count() > 1) {
                    $validator->errors()->add('items', "Chỉ được chọn một sản phẩm cho mục {$category}.");
                } elseif (! $slots->acceptsQuantity($category) && $quantity > 1) {
                    $validator->errors()->add('items', "Mục {$category} không được có số lượng lớn hơn 1.");
                } elseif ($quantity > $slots->maxQuantity($category)) {
                    $validator->errors()->add('items', "Mục {$category} tối đa {$slots->maxQuantity($category)}.");
                }
            }
        }];
    }
}
