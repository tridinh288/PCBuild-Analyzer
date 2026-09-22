<?php

namespace Tests\Feature\Admin;

use App\Models\Build;
use App\Models\Product;
use Illuminate\Http\UploadedFile;

class BuildAdminTest extends AdminTestCase
{
    private function id(string $name): int
    {
        return Product::where('name', $name)->value('id');
    }

    public function test_build_routes_require_a_token(): void
    {
        $this->getJson('/api/admin/builds')->assertUnauthorized();
        $this->postJson('/api/admin/builds', ['name' => 'X', 'purpose' => 'gaming'])->assertUnauthorized();
    }

    public function test_list_and_show_with_analysis(): void
    {
        $build = Build::where('slug', 'gaming-1440p-nguon-sat-gioi-han')->first();

        $this->actingAsAdmin()->getJson('/api/admin/builds?purpose=gaming')->assertOk()->assertJsonPath('meta.total', 6);

        $this->getJson("/api/admin/builds/{$build->id}")
            ->assertOk()
            ->assertJsonPath('data.build.slug', 'gaming-1440p-nguon-sat-gioi-han')
            ->assertJsonPath('data.build.total_price', 31_630_000)
            ->assertJsonPath('data.analysis.compatibility.status', 'warning');
    }

    public function test_create_update_and_delete_a_build(): void
    {
        $this->actingAsAdmin();

        $created = $this->postJson('/api/admin/builds', ['name' => 'Build thử nghiệm', 'purpose' => 'programming'])
            ->assertCreated()
            ->assertJsonPath('data.build.slug', 'build-thu-nghiem')
            ->assertJsonPath('data.build.is_featured', false)
            ->assertJsonPath('data.analysis.is_complete', false);
        $id = $created->json('data.build.id');

        $this->putJson("/api/admin/builds/{$id}", ['name' => 'Build thử nghiệm', 'purpose' => 'gaming', 'is_featured' => true])
            ->assertOk()
            ->assertJsonPath('data.build.purpose', 'gaming')
            ->assertJsonPath('data.build.is_featured', true);

        $this->deleteJson("/api/admin/builds/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('builds', ['id' => $id]);
    }

    public function test_build_details_are_validated(): void
    {
        $existing = Build::first();

        $this->actingAsAdmin()->postJson('/api/admin/builds', ['name' => '', 'purpose' => 'mining', 'slug' => $existing->slug])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'purpose', 'slug']);
    }

    public function test_items_are_replaced_and_incompatible_parts_are_reported_not_blocked(): void
    {
        $build = Build::factory()->create(['purpose' => 'gaming']);

        $response = $this->actingAsAdmin()->putJson("/api/admin/builds/{$build->id}/items", ['items' => [
            ['product_id' => $this->id('Intel Core i5-13400'), 'quantity' => 1],
            ['product_id' => $this->id('Gigabyte B650M GAMING X AX'), 'quantity' => 1],
            ['product_id' => $this->id('Kingston FURY Beast 16GB (2x8GB) DDR5 5600MHz'), 'quantity' => 2],
        ]])->assertOk();

        $response->assertJsonPath('data.analysis.compatibility.status', 'incompatible')
            ->assertJsonPath('data.analysis.compatibility.by_category.cpu', ['cpu_motherboard_socket'])
            ->assertJsonPath('data.build.total_price', 5_190_000 + 4_290_000 + 2 * 1_490_000);
        $this->assertSame(3, $build->items()->count());

        $this->putJson("/api/admin/builds/{$build->id}/items", ['items' => []])->assertOk();
        $this->assertSame(0, $build->items()->count());
    }

    public function test_item_structure_is_validated(): void
    {
        $build = Build::factory()->create();
        $cpu = $this->id('AMD Ryzen 5 7600');
        $this->actingAsAdmin();

        $this->putJson("/api/admin/builds/{$build->id}/items", ['items' => [
            ['product_id' => $cpu, 'quantity' => 1],
            ['product_id' => $this->id('AMD Ryzen 7 7700'), 'quantity' => 1],
        ]])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->putJson("/api/admin/builds/{$build->id}/items", ['items' => [['product_id' => $cpu, 'quantity' => 2]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->putJson("/api/admin/builds/{$build->id}/items", ['items' => [
            ['product_id' => $this->id('Kingston FURY Beast 16GB (2x8GB) DDR5 5600MHz'), 'quantity' => 5],
        ]])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->putJson("/api/admin/builds/{$build->id}/items", ['items' => [['product_id' => 999_999, 'quantity' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');

        $this->assertSame(0, $build->items()->count());
    }

    public function test_build_image_upload_and_delete_with_the_build(): void
    {
        $build = Build::factory()->create();
        $png = UploadedFile::fake()->createWithContent('b.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        $this->actingAsAdmin()->post("/api/admin/builds/{$build->id}/image", ['image' => $png], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.image.large', 'https://images.test/large/pcbuild/builds/fake-1');

        $this->deleteJson("/api/admin/builds/{$build->id}")->assertNoContent();
        $this->assertSame(['pcbuild/builds/fake-1'], $this->images->deleted);
    }
}
