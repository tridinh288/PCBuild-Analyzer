<?php

namespace App\Repositories\Contracts;

use App\Models\Build;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read queries for template builds. Results include the computed `total_price` (D-032).
 *
 * Supported filters: search, purpose, price_min, price_max, featured.
 * Sorts: price_asc, price_desc, newest (default).
 */
interface BuildRepositoryInterface
{
    /**
     * A template with its items, products and categories loaded.
     */
    public function findBySlug(string $slug): ?Build;

    /**
     * @return Collection<int, Build>
     */
    public function featured(int $limit = 6): Collection;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters = [], ?string $sort = null, int $perPage = 12): LengthAwarePaginator;
}
