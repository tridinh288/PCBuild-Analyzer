<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\Hardware\SpecSchema;

/**
 * Filter definitions for a category, so the frontend renders its filter panel from the API
 * instead of hardcoding one per page (spec section 22).
 */
class CatalogFilterService
{
    public const SORTS = [
        'name' => 'Tên A–Z',
        'price_asc' => 'Giá tăng dần',
        'price_desc' => 'Giá giảm dần',
    ];

    public function __construct(
        private readonly SpecSchema $schema,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forCategory(string $category): array
    {
        return [
            'category' => $category,
            'common' => [
                ['param' => 'search', 'type' => 'search', 'label' => 'Tìm kiếm'],
                ['param' => 'brand', 'type' => 'select', 'label' => 'Thương hiệu',
                    'options' => array_map(fn (string $brand) => ['value' => $brand, 'label' => $brand],
                        $this->products->brands($category))],
                ['param' => 'price', 'type' => 'range', 'label' => 'Giá (VND)',
                    'params' => ['price_min', 'price_max'], ...$this->products->priceRange($category)],
            ],
            'specs' => array_map(fn (string $param) => $this->specFilter($category, $param), array_keys($this->schema->filters($category))),
            'sorts' => array_map(fn (string $value, string $label) => ['value' => $value, 'label' => $label],
                array_keys(self::SORTS), self::SORTS),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function specFilter(string $category, string $param): array
    {
        $filter = $this->schema->filters($category)[$param];
        $spec = $this->schema->spec($category, $filter['key']);

        $definition = [
            'param' => $param,
            'key' => $filter['key'],
            'label' => $spec['label'],
            'filter' => $filter['filter'],
            'unit' => $spec['unit'] ?? null,
        ];

        if ($filter['enum'] !== null) {
            $definition['options'] = array_map(fn (string $code, string $label) => ['value' => $code, 'label' => $label],
                array_keys($this->schema->enum($filter['enum'])), $this->schema->enum($filter['enum']));
        }

        return $definition;
    }
}
