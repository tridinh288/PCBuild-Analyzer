<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_categories_are_listed_in_slot_order_with_slot_rules(): void
    {
        $response = $this->getJson('/api/categories')->assertOk();

        $this->assertSame(['cpu', 'motherboard', 'ram', 'gpu', 'storage', 'psu', 'case', 'cooler'],
            array_column($response->json('data'), 'slug'));
        $response->assertJsonPath('data.3.slot.required', 'unless_cpu_has_integrated_graphics')
            ->assertJsonPath('data.4.slot.multiple', true)
            ->assertJsonPath('data.2.slot.max_quantity', 4);
    }

    public function test_filter_definitions_come_from_config_and_data(): void
    {
        $response = $this->getJson('/api/categories/cpu/filters')->assertOk();

        $this->assertSame(['socket', 'cores_min', 'has_integrated_graphics'], array_column($response->json('data.specs'), 'param'));
        $response->assertJsonPath('data.specs.0.options.1', ['value' => 'am5', 'label' => 'AM5'])
            ->assertJsonPath('data.common.1.options.0.value', 'AMD')
            ->assertJsonPath('data.common.2.min', 2_190_000)
            ->assertJsonPath('data.common.2.max', 10_990_000);
    }

    public function test_filters_of_unknown_category_return_404(): void
    {
        $this->getJson('/api/categories/keyboard/filters')->assertNotFound()->assertJsonPath('success', false);
    }

    public function test_components_are_filtered_by_category_and_spec_and_paginated(): void
    {
        $response = $this->getJson('/api/components?category=cpu&socket=am5&sort=price_asc&per_page=2')->assertOk();

        $this->assertSame(['AMD Ryzen 5 7600', 'AMD Ryzen 7 7700'], array_column($response->json('data'), 'name'));
        $response->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.specs.0', [
                'key' => 'socket', 'label' => 'Socket', 'value' => 'am5', 'display' => 'AM5', 'highlight' => true,
            ]);
    }

    public function test_component_filters_are_validated(): void
    {
        $this->getJson('/api/components')->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->getJson('/api/components?category=cpu&socket=am6')->assertUnprocessable()->assertJsonValidationErrors('socket');
        $this->getJson('/api/components?category=cpu&cores_min=abc')->assertUnprocessable()->assertJsonValidationErrors('cores_min');
        $this->getJson('/api/components?category=cpu&sort=random')->assertUnprocessable()->assertJsonValidationErrors('sort');
    }

    public function test_component_detail_by_slug(): void
    {
        $this->getJson('/api/components/nzxt-h5-flow')
            ->assertOk()
            ->assertJsonPath('data.name', 'NZXT H5 Flow')
            ->assertJsonPath('data.category', 'case')
            ->assertJsonPath('data.specs.0.display', 'ATX, Micro-ATX, Mini-ITX');
    }

    public function test_inactive_or_unknown_component_returns_404(): void
    {
        Product::where('slug', 'nzxt-h5-flow')->update(['is_active' => false]);

        $this->getJson('/api/components/nzxt-h5-flow')->assertNotFound();
        $this->getJson('/api/components/does-not-exist')->assertNotFound();
    }
}
