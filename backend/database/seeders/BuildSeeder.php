<?php

namespace Database\Seeders;

use App\Models\Build;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Seeds template builds from database/seeders/data/builds.php.
 * Items reference products by name; an unknown name stops the seeder.
 */
class BuildSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/builds.php' as $data) {
            $build = Build::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'purpose' => $data['purpose'],
                    'is_featured' => $data['is_featured'] ?? false,
                ],
            );

            $build->items()->delete();

            foreach ($this->normalizeItems($data['items']) as $name => $quantity) {
                $build->items()->create([
                    'product_id' => $this->productId($name, $data['name']),
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    /**
     * Items are either 'Product name' or 'Product name' => quantity.
     *
     * @param  array<int|string, string|int>  $items
     * @return array<string, int>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $key => $value) {
            is_int($key) ? $normalized[$value] = 1 : $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function productId(string $productName, string $buildName): int
    {
        return Product::where('slug', Str::slug($productName))->value('id')
            ?? throw new RuntimeException("Build \"{$buildName}\" references unknown product \"{$productName}\".");
    }
}
