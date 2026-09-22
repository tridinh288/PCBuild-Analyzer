<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\Hardware\SpecSchema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private readonly SpecSchema $schema) {}

    public function findBySlug(string $slug): ?Product
    {
        return Product::query()->active()->with('category')->where('slug', $slug)->first();
    }

    public function findActiveByIds(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return Product::query()->active()->with('category')->whereIn('id', array_unique($ids))->get();
    }

    public function search(string $category, array $filters = [], ?string $sort = null): Collection
    {
        return $this->query($category, $filters, $sort)->get();
    }

    public function brands(string $category): array
    {
        return Product::query()->active()->whereRelation('category', 'slug', $category)
            ->distinct()->orderBy('brand')->pluck('brand')->all();
    }

    public function priceRange(string $category): array
    {
        $range = Product::query()->active()->whereRelation('category', 'slug', $category)
            ->selectRaw('MIN(price) AS min_price, MAX(price) AS max_price')->first();

        return ['min' => (int) $range->min_price, 'max' => (int) $range->max_price];
    }

    public function paginate(array $filters = [], ?string $sort = null, int $perPage = 12): LengthAwarePaginator
    {
        $category = $filters['category'] ?? null;

        return $this->query($category, $filters, $sort)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    private function query(?string $category, array $filters, ?string $sort): Builder
    {
        $query = Product::query()->active()->with('category');

        if ($category !== null) {
            $query->whereRelation('category', 'slug', $category);
            $this->applySpecFilters($query, $category, $filters);
        }

        $this->applyCommonFilters($query, $filters);

        return $this->applySort($query, $sort);
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyCommonFilters(Builder $query, array $filters): void
    {
        if (filled($filters['search'] ?? null)) {
            $term = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q
                ->where('name', 'like', $term)
                ->orWhere('brand', 'like', $term)
                ->orWhere('model', 'like', $term));
        }

        if (filled($filters['brand'] ?? null)) {
            $query->where('brand', $filters['brand']);
        }

        if (filled($filters['price_min'] ?? null)) {
            $query->where('price', '>=', (int) $filters['price_min']);
        }

        if (filled($filters['price_max'] ?? null)) {
            $query->where('price', '<=', (int) $filters['price_max']);
        }
    }

    /**
     * Spec filters come from config/hardware.php, so a new filterable spec needs no code here.
     *
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySpecFilters(Builder $query, string $category, array $filters): void
    {
        foreach ($this->schema->filters($category) as $param => $definition) {
            $value = $filters[$param] ?? null;

            if (! filled($value)) {
                continue;
            }

            $column = 'specs->'.$definition['key'];

            match ($definition['filter']) {
                'exact' => $query->where($column, $definition['type'] === 'integer' ? (int) $value : (string) $value),
                'boolean' => $query->where($column, filter_var($value, FILTER_VALIDATE_BOOL)),
                'min' => $query->where($column, '>=', (int) $value),
                'max' => $query->where($column, '<=', (int) $value),
                'contains' => $query->whereJsonContains($column, (string) $value),
            };
        }
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function applySort(Builder $query, ?string $sort): Builder
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->orderBy('name'),
        };

        return $query->orderBy('id');
    }
}
