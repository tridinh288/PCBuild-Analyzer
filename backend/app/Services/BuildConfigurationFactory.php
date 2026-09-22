<?php

namespace App\Services;

use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Configuration\SlotRules;
use App\Domain\Hardware\Components\HardwareComponent;
use App\Domain\Hardware\HardwareFactory;
use App\Models\Build;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use InvalidArgumentException;

/**
 * Factory Pattern: builds a BuildConfiguration from either source (spec section 10).
 * Lives in Services, not Domain, because it loads products from the database (D-030).
 */
class BuildConfigurationFactory
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly HardwareFactory $hardware,
        private readonly SlotRules $slots,
    ) {}

    public function empty(): BuildConfiguration
    {
        return BuildConfiguration::empty($this->slots);
    }

    /**
     * A template, including products that were deactivated after it was created (D-007).
     */
    public function fromBuild(Build $build): BuildConfiguration
    {
        $build->loadMissing('items.product.category');
        $config = $this->empty();

        foreach ($build->items as $item) {
            $config = $config->with($this->component($item->product), $item->quantity);
        }

        return $config;
    }

    /**
     * A custom selection from the Builder URL or request body:
     *   { "cpu": 3, "ram": { "id": 12, "quantity": 2 }, "storage": [4, { "id": 9, "quantity": 2 }] }
     *
     * Unknown, inactive, or wrong-category IDs are reported in `missing` instead of failing.
     *
     * @param  array<string, mixed>  $selection
     */
    public function fromSelection(array $selection): ResolvedSelection
    {
        $wanted = $this->normalize($selection);
        $products = $this->products->findActiveByIds(array_column($wanted, 'id'))->keyBy('id');

        $config = $this->empty();
        $missing = [];

        foreach ($wanted as ['category' => $category, 'id' => $id, 'quantity' => $quantity]) {
            $product = $products->get($id);

            if ($product === null || $product->category->slug !== $category) {
                $missing[] = ['category' => $category, 'id' => $id];

                continue;
            }

            $config = $config->with($this->component($product), $quantity);
        }

        return new ResolvedSelection($config, $missing);
    }

    public function component(Product $product): HardwareComponent
    {
        return $this->hardware->make($product->category->slug, [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'specs' => $product->specs,
            'slug' => $product->slug,
            'brand' => $product->brand,
        ]);
    }

    /**
     * Flattens every accepted selection shape to a list of (category, id, quantity).
     * Shape and bounds are validated by the Form Request; this only guards the categories.
     *
     * @param  array<string, mixed>  $selection
     * @return list<array{category: string, id: int, quantity: int}>
     */
    private function normalize(array $selection): array
    {
        $normalized = [];

        foreach ($selection as $category => $value) {
            if (! $this->slots->has($category)) {
                throw new InvalidArgumentException("Unknown builder slot [{$category}].");
            }

            $entries = is_array($value) && array_is_list($value) ? $value : [$value];

            foreach ($entries as $entry) {
                $normalized[] = [
                    'category' => $category,
                    'id' => (int) (is_array($entry) ? $entry['id'] : $entry),
                    'quantity' => (int) (is_array($entry) ? ($entry['quantity'] ?? 1) : 1),
                ];
            }
        }

        return $normalized;
    }
}
