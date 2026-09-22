<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Support\Hardware\SpecSchema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Seeds demo products from database/seeders/data/<category>.php.
 * Every product is validated against config/hardware.php first: an unknown key
 * or a wrong type stops the seeder instead of storing bad data (D-004).
 */
class ProductSeeder extends Seeder
{
    public function __construct(private readonly SpecSchema $schema) {}

    public function run(): void
    {
        foreach ($this->schema->categories() as $category) {
            $this->seedCategory($category, require __DIR__."/data/{$category}.php");
        }
    }

    /**
     * @param  list<array<string, mixed>>  $products
     */
    public function seedCategory(string $category, array $products): void
    {
        $categoryId = Category::where('slug', $category)->valueOrFail('id');

        foreach ($products as $product) {
            $this->assertValidSpecs($category, $product);

            Product::updateOrCreate(
                ['slug' => Str::slug($product['name'])],
                [
                    'category_id' => $categoryId,
                    'name' => $product['name'],
                    'brand' => $product['brand'],
                    'model' => $product['model'],
                    'price' => $product['price'],
                    'description' => $product['description'] ?? null,
                    'specs' => $product['specs'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function assertValidSpecs(string $category, array $product): void
    {
        $validator = Validator::make(['specs' => $product['specs']], $this->schema->rulesFor($category));

        if ($validator->fails()) {
            throw new RuntimeException(sprintf(
                'Invalid specs for %s "%s": %s',
                $category,
                $product['name'],
                json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE),
            ));
        }
    }
}
