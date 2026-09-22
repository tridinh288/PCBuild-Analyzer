<?php

namespace Tests\Feature\Models;

use App\Enums\BuildPurpose;
use App\Models\Build;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_price_multiplies_price_by_quantity(): void
    {
        $build = Build::factory()->create();
        $cpu = Product::factory()->create(['price' => 5_000_000]);
        $ram = Product::factory()->create(['price' => 1_200_000]);
        $build->items()->create(['product_id' => $cpu->id, 'quantity' => 1]);
        $build->items()->create(['product_id' => $ram->id, 'quantity' => 2]);

        $total = Build::query()->withTotalPrice()->find($build->id)->total_price;

        $this->assertSame(7_400_000, (int) $total);
    }

    public function test_total_price_is_zero_for_a_build_without_items(): void
    {
        $build = Build::factory()->create();

        $this->assertSame(0, (int) Build::query()->withTotalPrice()->find($build->id)->total_price);
    }

    public function test_total_price_can_be_used_for_sorting(): void
    {
        $cheap = Build::factory()->create();
        $expensive = Build::factory()->create();
        $cheap->items()->create(['product_id' => Product::factory()->create(['price' => 1_000_000])->id]);
        $expensive->items()->create(['product_id' => Product::factory()->create(['price' => 9_000_000])->id]);

        $ids = Build::query()->withTotalPrice()->orderByDesc('total_price')->pluck('id')->all();

        $this->assertSame([$expensive->id, $cheap->id], $ids);
    }

    public function test_purpose_is_cast_to_enum(): void
    {
        $build = Build::factory()->create(['purpose' => BuildPurpose::Gaming]);

        $this->assertSame(BuildPurpose::Gaming, $build->fresh()->purpose);
        $this->assertSame('Chơi game', $build->purpose->label());
    }

    public function test_deleting_a_build_deletes_its_items(): void
    {
        $build = Build::factory()->create();
        $build->items()->create(['product_id' => Product::factory()->create()->id]);

        $build->delete();

        $this->assertDatabaseCount('build_items', 0);
    }

    public function test_a_product_used_in_a_build_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();
        Build::factory()->create()->items()->create(['product_id' => $product->id]);

        $this->expectException(QueryException::class);

        $product->delete();
    }
}
