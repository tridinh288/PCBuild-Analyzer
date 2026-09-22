<?php

namespace App\Models;

use App\Enums\BuildPurpose;
use Database\Factories\BuildFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An admin-curated template build. Users never modify it (D-002).
 */
#[Fillable(['name', 'slug', 'description', 'purpose', 'image_public_id', 'is_featured'])]
class Build extends Model
{
    /** @use HasFactory<BuildFactory> */
    use HasFactory;

    /**
     * @return HasMany<BuildItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BuildItem::class);
    }

    /**
     * Adds a computed `total_price` column: SUM(price × quantity) over the items (D-032).
     * withSum() cannot be used because price and quantity live in different tables.
     */
    #[Scope]
    protected function withTotalPrice(Builder $query): void
    {
        if ($query->getQuery()->columns === null) {
            $query->select($this->qualifyColumn('*'));
        }

        $query->addSelect(['total_price' => BuildItem::query()
            ->join('products', 'products.id', '=', 'build_items.product_id')
            ->whereColumn('build_items.build_id', $this->qualifyColumn('id'))
            ->selectRaw('COALESCE(SUM(products.price * build_items.quantity), 0)'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => BuildPurpose::class,
            'is_featured' => 'boolean',
        ];
    }
}
