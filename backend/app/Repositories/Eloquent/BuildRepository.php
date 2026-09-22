<?php

namespace App\Repositories\Eloquent;

use App\Models\Build;
use App\Repositories\Contracts\BuildRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BuildRepository implements BuildRepositoryInterface
{
    public function findBySlug(string $slug): ?Build
    {
        return Build::query()
            ->withTotalPrice()
            ->with('items.product.category')
            ->where('slug', $slug)
            ->first();
    }

    public function featured(int $limit = 6): Collection
    {
        return Build::query()
            ->withTotalPrice()
            ->where('is_featured', true)
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function search(array $filters = [], ?string $sort = null, int $perPage = 12): LengthAwarePaginator
    {
        $query = Build::query()->withTotalPrice();

        if (filled($filters['search'] ?? null)) {
            $term = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('description', 'like', $term));
        }

        if (filled($filters['purpose'] ?? null)) {
            $query->where('purpose', $filters['purpose']);
        }

        if (filter_var($filters['featured'] ?? false, FILTER_VALIDATE_BOOL)) {
            $query->where('is_featured', true);
        }

        // total_price is a computed column, so price filters use HAVING (verified on TiDB, D-033).
        if (filled($filters['price_min'] ?? null)) {
            $query->having('total_price', '>=', (int) $filters['price_min']);
        }

        if (filled($filters['price_max'] ?? null)) {
            $query->having('total_price', '<=', (int) $filters['price_max']);
        }

        match ($sort) {
            'price_asc' => $query->orderBy('total_price'),
            'price_desc' => $query->orderByDesc('total_price'),
            default => $query->latest(),
        };

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }
}
