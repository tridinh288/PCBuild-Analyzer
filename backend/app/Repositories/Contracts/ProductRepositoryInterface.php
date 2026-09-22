<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read queries for products. Filtering is SQL only; compatibility is decided later
 * by the engine in PHP (D-013).
 *
 * Supported filters: search, brand, price_min, price_max, category (paginate only),
 * plus the category's spec filters from config/hardware.php.
 * Sorts: price_asc, price_desc, name (default).
 */
interface ProductRepositoryInterface
{
    /**
     * An active product by slug (public detail page); inactive products are not found.
     */
    public function findBySlug(string $slug): ?Product;

    /**
     * Active products among the given IDs, with their category loaded. Unknown or
     * inactive IDs are simply absent from the result.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    public function findActiveByIds(array $ids): Collection;

    /**
     * All active products of a category matching the filters (Builder candidates).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Product>
     */
    public function search(string $category, array $filters = [], ?string $sort = null): Collection;

    /**
     * Distinct brands of the active products of a category, sorted (filter options).
     *
     * @return list<string>
     */
    public function brands(string $category): array;

    /**
     * Lowest and highest price of the active products of a category.
     *
     * @return array{min: int, max: int}
     */
    public function priceRange(string $category): array;

    /**
     * Active products, paginated (public catalog).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], ?string $sort = null, int $perPage = 12): LengthAwarePaginator;
}
