<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_specs_are_stored_as_json_and_read_as_array(): void
    {
        $product = Product::factory()->create([
            'specs' => ['socket' => 'am5', 'tdp' => 65, 'has_integrated_graphics' => true],
        ]);

        $specs = $product->fresh()->specs;

        $this->assertSame('am5', $specs['socket']);
        $this->assertSame(65, $specs['tdp']);
        $this->assertTrue($specs['has_integrated_graphics']);
    }

    public function test_active_scope_hides_inactive_products(): void
    {
        $active = Product::factory()->create();
        Product::factory()->inactive()->create();

        $this->assertSame([$active->id], Product::query()->active()->pluck('id')->all());
    }
}
