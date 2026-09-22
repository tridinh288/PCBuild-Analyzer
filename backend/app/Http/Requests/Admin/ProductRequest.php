<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Support\Hardware\SpecSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create and update a product. Spec rules come from config/hardware.php through SpecSchema —
 * the same rules the seeders use (D-004). The category is chosen on create and fixed after,
 * because the specs depend on it.
 */
class ProductRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $schema = $this->container->make(SpecSchema::class);
        $product = $this->route('product');
        $category = $this->category();

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($product?->id)],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($product === null) {
            $rules['category'] = ['required', 'string', Rule::in($schema->categories())];
        }

        if (is_string($category) && $schema->hasCategory($category)) {
            $rules += $schema->rulesFor($category);
        }

        return $rules;
    }

    /**
     * Cross-field rule not expressible per spec: an NVMe drive is always M.2.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $specs = $this->input('specs', []);

            if ($this->category() === 'storage' && ($specs['interface'] ?? null) === 'nvme' && ($specs['form'] ?? null) !== 'm2') {
                $validator->errors()->add('specs.form', 'Ổ NVMe phải có kiểu dáng M.2.');
            }
        }];
    }

    public function category(): ?string
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return $product?->category->slug ?? $this->input('category');
    }
}
