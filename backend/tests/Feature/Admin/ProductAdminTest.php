<?php

namespace Tests\Feature\Admin;

use App\Models\Product;

class ProductAdminTest extends AdminTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function cpuPayload(array $overrides = []): array
    {
        return [
            'category' => 'cpu',
            'name' => 'AMD Ryzen 9 9900X',
            'brand' => 'AMD',
            'model' => '9900X',
            'price' => 12_490_000,
            'specs' => [
                'socket' => 'am5', 'cores' => 12, 'threads' => 24, 'tdp' => 120,
                'has_integrated_graphics' => true, 'includes_cooler' => false, 'performance_tier' => 90,
            ],
            ...$overrides,
        ];
    }

    public function test_product_routes_require_a_token(): void
    {
        $this->getJson('/api/admin/products')->assertUnauthorized();
        $this->postJson('/api/admin/products', $this->cpuPayload())->assertUnauthorized();
    }

    public function test_list_includes_inactive_products_and_usage(): void
    {
        Product::where('slug', 'nzxt-h5-flow')->update(['is_active' => false]);

        $response = $this->actingAsAdmin()->getJson('/api/admin/products?category=case&is_active=0')->assertOk();

        $response->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'nzxt-h5-flow')
            ->assertJsonPath('data.0.is_active', false);
        $this->assertGreaterThan(0, $response->json('data.0.used_in_builds'));
    }

    public function test_create_product_with_generated_slug(): void
    {
        $this->actingAsAdmin()->postJson('/api/admin/products', $this->cpuPayload())
            ->assertCreated()
            ->assertJsonPath('data.slug', 'amd-ryzen-9-9900x')
            ->assertJsonPath('data.category', 'cpu')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.raw_specs.tdp', 120);

        // Same name again gets a unique slug.
        $this->postJson('/api/admin/products', $this->cpuPayload())->assertCreated()->assertJsonPath('data.slug', 'amd-ryzen-9-9900x-2');
    }

    public function test_specs_are_validated_against_config(): void
    {
        $payload = $this->cpuPayload();
        $payload['specs']['tdp'] = '120W';
        $payload['specs']['socket'] = 'am6';
        $payload['specs']['color'] = 'red';
        unset($payload['specs']['cores']);

        $this->actingAsAdmin()->postJson('/api/admin/products', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['specs', 'specs.tdp', 'specs.socket', 'specs.cores']);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->actingAsAdmin()->postJson('/api/admin/products', $this->cpuPayload(['category' => 'keyboard']))
            ->assertUnprocessable()->assertJsonValidationErrors('category');
    }

    public function test_nvme_drive_must_be_m2(): void
    {
        $errors = $this->actingAsAdmin()->postJson('/api/admin/products', [
            'category' => 'storage', 'name' => 'Odd drive', 'brand' => 'X', 'model' => 'Y', 'price' => 1,
            'specs' => ['interface' => 'nvme', 'form' => '2_5', 'capacity_gb' => 1000],
        ])->assertUnprocessable()->json('errors');

        $this->assertSame(['Ổ NVMe phải có kiểu dáng M.2.'], $errors['specs.form']);
    }

    public function test_conditional_specs_follow_the_cooler_type(): void
    {
        $aio = [
            'category' => 'cooler', 'name' => 'Arctic Liquid Freezer III 240', 'brand' => 'Arctic', 'model' => 'LF3-240', 'price' => 2_000_000,
            'specs' => ['type' => 'aio', 'supported_sockets' => ['am5', 'lga1700'], 'max_tdp' => 250, 'height_mm' => null],
        ];

        $this->actingAsAdmin()->postJson('/api/admin/products', $aio)
            ->assertUnprocessable()->assertJsonValidationErrors('specs.radiator_mm');

        $aio['specs']['radiator_mm'] = 240;
        $response = $this->postJson('/api/admin/products', $aio)->assertCreated();

        // Empty optional specs are not stored.
        $this->assertArrayNotHasKey('height_mm', Product::find($response->json('data.id'))->specs);
    }

    public function test_update_keeps_the_category_and_can_deactivate(): void
    {
        $product = Product::where('slug', 'amd-ryzen-5-7600')->first();
        $payload = $this->cpuPayload(['category' => 'gpu', 'name' => 'AMD Ryzen 5 7600', 'price' => 4_990_000, 'is_active' => false]);

        $this->actingAsAdmin()->putJson("/api/admin/products/{$product->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.category', 'cpu')
            ->assertJsonPath('data.price', 4_990_000)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_show_returns_raw_specs_for_editing(): void
    {
        $product = Product::where('slug', 'noctua-nh-d15')->first();

        $this->actingAsAdmin()->getJson("/api/admin/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.raw_specs.height_mm', 165)
            ->assertJsonPath('data.used_in_builds', 1);

        $this->getJson('/api/admin/products/999999')->assertNotFound();
    }

    public function test_product_used_by_a_template_cannot_be_deleted(): void
    {
        $product = Product::where('slug', 'nzxt-h5-flow')->first();

        $this->actingAsAdmin()->deleteJson("/api/admin/products/{$product->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
        $this->assertModelExists($product);
    }

    public function test_unused_product_can_be_deleted(): void
    {
        $id = $this->actingAsAdmin()->postJson('/api/admin/products', $this->cpuPayload())->json('data.id');

        $this->deleteJson("/api/admin/products/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('products', ['id' => $id]);
    }
}
