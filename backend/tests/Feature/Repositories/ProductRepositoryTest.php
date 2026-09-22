<?php

namespace Tests\Feature\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function repository(): ProductRepositoryInterface
    {
        return $this->app->make(ProductRepositoryInterface::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<string>
     */
    private function names(string $category, array $filters, ?string $sort = null): array
    {
        return $this->repository()->search($category, $filters, $sort)->pluck('name')->all();
    }

    public function test_search_returns_only_the_requested_category(): void
    {
        $products = $this->repository()->search('psu');

        $this->assertCount(7, $products);
        $this->assertTrue($products->every(fn (Product $p) => $p->category->slug === 'psu'));
    }

    public function test_exact_enum_filter(): void
    {
        $this->assertEqualsCanonicalizing(
            ['AMD Ryzen 5 7600', 'AMD Ryzen 7 7700', 'AMD Ryzen 7 7800X3D'],
            $this->names('cpu', ['socket' => 'am5']),
        );
    }

    public function test_min_filter_uses_numeric_comparison(): void
    {
        $this->assertEqualsCanonicalizing(
            ['Samsung 990 PRO 2TB NVMe', 'Seagate BarraCuda 2TB HDD'],
            $this->names('storage', ['capacity_gb_min' => 2000]),
        );
    }

    public function test_max_filter(): void
    {
        $this->assertSame(
            ['Gigabyte GeForce RTX 3050 WINDFORCE OC 6G', 'MSI GeForce RTX 4060 Ti VENTUS 2X 8G'],
            $this->names('gpu', ['length_mm_max' => 200], 'price_asc'),
        );
    }

    public function test_boolean_filter_accepts_query_string_values(): void
    {
        $this->assertEqualsCanonicalizing(
            ['AMD Ryzen 5 5600', 'Intel Core i3-12100F'],
            $this->names('cpu', ['has_integrated_graphics' => '0']),
        );
    }

    public function test_contains_filter_on_list_spec_uses_the_configured_param_name(): void
    {
        $this->assertSame(['Cooler Master MasterBox NR200P', 'Deepcool CH370', 'Lian Li O11 Dynamic EVO', 'NZXT H5 Flow', 'Xigmatek NYX Air 3F'],
            $this->names('case', ['form_factor' => 'itx']));
        $this->assertSame(['Lian Li O11 Dynamic EVO', 'NZXT H5 Flow'], $this->names('case', ['form_factor' => 'atx']));
    }

    public function test_common_filters_and_price_sort(): void
    {
        $prices = $this->repository()
            ->search('gpu', ['brand' => 'ASUS', 'price_max' => 20_000_000], 'price_desc')
            ->pluck('price')->all();

        $this->assertSame([17_990_000, 7_990_000], $prices);
    }

    public function test_search_term_matches_name_brand_or_model(): void
    {
        $this->assertSame(['Noctua NH-D15', 'Noctua NH-L9a-AM5'], $this->names('cooler', ['search' => 'noctua']));
        $this->assertSame(['Kingston A400 480GB SATA'], $this->names('storage', ['search' => 'SA400S37']));
    }

    public function test_filters_of_another_category_are_ignored(): void
    {
        $this->assertCount(7, $this->repository()->search('psu', ['socket' => 'am5']));
    }

    public function test_inactive_products_are_excluded(): void
    {
        Product::where('name', 'Deepcool PF450 450W')->update(['is_active' => false]);

        $this->assertNotContains('Deepcool PF450 450W', $this->names('psu', []));
        $this->assertNull($this->repository()->findBySlug('deepcool-pf450-450w'));
    }

    public function test_find_active_by_ids_skips_unknown_and_inactive_ids(): void
    {
        $active = Product::where('name', 'NZXT H5 Flow')->value('id');
        $inactive = Product::where('name', 'Deepcool CH370')->value('id');
        Product::whereKey($inactive)->update(['is_active' => false]);

        $found = $this->repository()->findActiveByIds([$active, $inactive, 999_999]);

        $this->assertSame([$active], $found->pluck('id')->all());
        $this->assertTrue($found->first()->relationLoaded('category'));
    }

    public function test_paginate_filters_by_category_and_reports_totals(): void
    {
        $page = $this->repository()->paginate(['category' => 'cpu', 'socket' => 'lga1700'], 'price_asc', 2);

        $this->assertSame(3, $page->total());
        $this->assertSame(['Intel Core i3-12100F', 'Intel Core i5-13400'], $page->getCollection()->pluck('name')->all());
    }
}
