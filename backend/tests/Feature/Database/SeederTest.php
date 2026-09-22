<?php

namespace Tests\Feature\Database;

use App\Models\Build;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_matches_the_sample_data_plan(): void
    {
        config(['admin.email' => 'admin@example.test', 'admin.password' => 'secret-password']);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(8, Category::count());

        $perCategory = Product::query()
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.slug, COUNT(*) as total')
            ->groupBy('categories.slug')
            ->pluck('total', 'slug');

        foreach ($perCategory as $slug => $total) {
            $this->assertGreaterThanOrEqual(5, $total, "{$slug} needs at least 5 products");
        }

        $this->assertGreaterThanOrEqual(8, Build::count());
        $this->assertSame(0, Build::doesntHave('items')->count());
        $this->assertTrue(User::where('email', 'admin@example.test')->exists());
    }

    public function test_seeding_twice_does_not_duplicate_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [Product::count(), Build::count(), \App\Models\BuildItem::count()];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [Product::count(), Build::count(), \App\Models\BuildItem::count()]);
    }

    public function test_admin_is_not_seeded_without_credentials(): void
    {
        config(['admin.email' => null, 'admin.password' => null]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
    }

    public function test_product_with_invalid_specs_stops_the_seeder(): void
    {
        $this->seed(CategorySeeder::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid specs for cpu "Broken CPU"');

        $this->app->make(ProductSeeder::class)->seedCategory('cpu', [[
            'name' => 'Broken CPU', 'brand' => 'X', 'model' => 'X', 'price' => 1,
            'specs' => ['socket' => 'am5', 'tdp' => '65W'],
        ]]);
    }
}
