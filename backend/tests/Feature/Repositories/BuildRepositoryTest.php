<?php

namespace Tests\Feature\Repositories;

use App\Enums\BuildPurpose;
use App\Models\Build;
use App\Repositories\Contracts\BuildRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function repository(): BuildRepositoryInterface
    {
        return $this->app->make(BuildRepositoryInterface::class);
    }

    public function test_find_by_slug_loads_items_and_total_price(): void
    {
        $build = $this->repository()->findBySlug('gaming-1080p-am5');

        $this->assertTrue($build->relationLoaded('items'));
        $this->assertTrue($build->items->first()->product->relationLoaded('category'));
        $this->assertSame(26_330_000, (int) $build->total_price);
    }

    public function test_find_by_slug_returns_null_for_unknown_slug(): void
    {
        $this->assertNull($this->repository()->findBySlug('does-not-exist'));
    }

    public function test_featured_returns_only_featured_builds(): void
    {
        $featured = $this->repository()->featured();

        $this->assertNotEmpty($featured);
        $this->assertTrue($featured->every(fn (Build $b) => $b->is_featured));
    }

    public function test_filter_by_purpose(): void
    {
        $page = $this->repository()->search(['purpose' => 'programming']);

        $this->assertSame(2, $page->total());
        $this->assertTrue($page->getCollection()->every(fn (Build $b) => $b->purpose === BuildPurpose::Programming));
    }

    public function test_price_range_uses_the_computed_total_and_sorts_by_it(): void
    {
        $page = $this->repository()->search(['price_min' => 20_000_000, 'price_max' => 35_000_000], 'price_asc');

        $totals = $page->getCollection()->pluck('total_price')->map(fn ($t) => (int) $t)->all();

        $this->assertSame([21_330_000, 21_940_000, 26_330_000, 31_630_000, 34_720_000], $totals);
        $this->assertSame(5, $page->total());
    }

    public function test_search_matches_name(): void
    {
        $page = $this->repository()->search(['search' => 'Mini-ITX']);

        $this->assertSame(['mini-itx-nho-gon'], $page->getCollection()->pluck('slug')->all());
    }

    public function test_pagination(): void
    {
        $page = $this->repository()->search([], 'price_desc', 3);

        $this->assertSame(10, $page->total());
        $this->assertSame(4, $page->lastPage());
        $this->assertSame('gaming-4k-cao-cap', $page->getCollection()->first()->slug);
    }
}
