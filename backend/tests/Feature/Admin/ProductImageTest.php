<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use Illuminate\Http\UploadedFile;

class ProductImageTest extends AdminTestCase
{
    /** A real 1×1 PNG, so the `image` rule inspects actual image bytes (no GD needed). */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function png(string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG));
    }

    private function product(): Product
    {
        return Product::where('slug', 'nzxt-h5-flow')->first();
    }

    public function test_upload_stores_the_public_id_and_returns_urls(): void
    {
        $product = $this->product();

        $this->actingAsAdmin()->post("/api/admin/products/{$product->id}/image", ['image' => $this->png()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.image_public_id', 'pcbuild/products/fake-1')
            ->assertJsonPath('data.image.thumb', 'https://images.test/thumb/pcbuild/products/fake-1');

        $this->assertSame('pcbuild/products/fake-1', $product->fresh()->image_public_id);
        $this->assertSame('photo.png', $this->images->uploaded[0]['name']);
    }

    public function test_public_api_returns_image_urls(): void
    {
        $this->product()->update(['image_public_id' => 'pcbuild/products/abc']);

        $this->getJson('/api/components/nzxt-h5-flow')
            ->assertJsonPath('data.image', [
                'thumb' => 'https://images.test/thumb/pcbuild/products/abc',
                'large' => 'https://images.test/large/pcbuild/products/abc',
            ]);
    }

    public function test_replacing_deletes_the_old_image_after_the_new_upload(): void
    {
        $product = $this->product();
        $product->update(['image_public_id' => 'pcbuild/products/old']);

        $this->actingAsAdmin()->post("/api/admin/products/{$product->id}/image", ['image' => $this->png()], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame(['pcbuild/products/old'], $this->images->deleted);
    }

    public function test_failed_upload_keeps_the_current_image(): void
    {
        $product = $this->product();
        $product->update(['image_public_id' => 'pcbuild/products/old']);
        $this->images->failUploads = true;

        $this->actingAsAdmin()->post("/api/admin/products/{$product->id}/image", ['image' => $this->png()], ['Accept' => 'application/json'])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Không tải được ảnh lên, vui lòng thử lại.');

        $this->assertSame('pcbuild/products/old', $product->fresh()->image_public_id);
        $this->assertSame([], $this->images->deleted);
    }

    public function test_file_type_and_size_are_validated(): void
    {
        $product = $this->product();
        $this->actingAsAdmin();

        $this->post("/api/admin/products/{$product->id}/image", ['image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->post("/api/admin/products/{$product->id}/image", ['image' => UploadedFile::fake()->create('big.png', 3000, 'image/png')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson("/api/admin/products/{$product->id}/image", [])
            ->assertUnprocessable()->assertJsonValidationErrors('image');

        $this->assertSame([], $this->images->uploaded);
    }

    public function test_remove_image(): void
    {
        $product = $this->product();
        $product->update(['image_public_id' => 'pcbuild/products/old']);

        $this->actingAsAdmin()->deleteJson("/api/admin/products/{$product->id}/image")
            ->assertOk()->assertJsonPath('data.image', null);

        $this->assertNull($product->fresh()->image_public_id);
        $this->assertSame(['pcbuild/products/old'], $this->images->deleted);
    }

    public function test_deleting_a_product_deletes_its_image(): void
    {
        $id = Product::factory()->create(['image_public_id' => 'pcbuild/products/lonely'])->id;

        $this->actingAsAdmin()->deleteJson("/api/admin/products/{$id}")->assertNoContent();

        $this->assertSame(['pcbuild/products/lonely'], $this->images->deleted);
    }
}
