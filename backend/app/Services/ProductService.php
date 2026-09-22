<?php

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Models\Category;
use App\Models\Product;
use App\Support\Images\ImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Admin writes for products, including their image on the image service (D-022).
 */
class ProductService
{
    public function __construct(private readonly ImageStorage $images) {}

    /**
     * @param  array<string, mixed>  $data  Validated ProductRequest data
     */
    public function create(array $data): Product
    {
        $product = new Product(['is_active' => true, ...$this->attributes($data)]);
        $product->category_id = Category::where('slug', $data['category'])->valueOrFail('id');
        $product->slug = $this->uniqueSlug($data['slug'] ?? $data['name']);
        $product->save();

        return $product->load('category');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->fill($this->attributes($data));

        if (filled($data['slug'] ?? null)) {
            $product->slug = $data['slug'];
        }

        $product->save();

        return $product->load('category');
    }

    /**
     * Products used by templates cannot be deleted (FK RESTRICT, D-007): the admin is asked to
     * deactivate them instead, so templates and shared links keep working.
     */
    public function delete(Product $product): void
    {
        $uses = $product->buildItems()->count();

        if ($uses > 0) {
            throw new ConflictException("Linh kiện đang được dùng trong {$uses} cấu hình mẫu. Hãy chuyển sang ngừng kinh doanh thay vì xóa.");
        }

        $product->delete();

        if ($product->image_public_id) {
            $this->images->delete($product->image_public_id);
        }
    }

    /**
     * Upload first, then switch the product to the new image, then delete the old one:
     * a failed upload leaves the current image untouched.
     */
    public function replaceImage(Product $product, UploadedFile $file): Product
    {
        $previous = $product->image_public_id;

        $product->image_public_id = $this->images->upload($file, config('images.folders.products'));
        $product->save();

        if ($previous) {
            $this->images->delete($previous);
        }

        return $product->load('category');
    }

    public function removeImage(Product $product): Product
    {
        if ($product->image_public_id) {
            $this->images->delete($product->image_public_id);
            $product->image_public_id = null;
            $product->save();
        }

        return $product->load('category');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return collect($data)->only(['name', 'brand', 'model', 'price', 'description', 'is_active', 'specs'])
            // Optional specs left empty in the form are not stored.
            ->map(fn ($value, $key) => $key === 'specs' ? array_filter($value, fn ($spec) => $spec !== null) : $value)
            ->all();
    }

    /**
     * "AMD Ryzen 5 7600" → "amd-ryzen-5-7600", then "-2", "-3"… if taken.
     */
    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source);
        $slug = $base;

        for ($i = 2; Product::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
