<?php

namespace Tests\Feature\Admin;

use App\Models\Category;

class CategoryAdminTest extends AdminTestCase
{
    public function test_admin_routes_require_a_token(): void
    {
        $category = Category::where('slug', 'cpu')->first();

        $this->getJson('/api/admin/categories')->assertUnauthorized();
        $this->putJson("/api/admin/categories/{$category->id}", ['name' => 'X', 'sort_order' => 1])->assertUnauthorized();
        $this->getJson('/api/admin/categories/cpu/spec-schema')->assertUnauthorized();
    }

    public function test_categories_are_listed_with_product_counts(): void
    {
        $this->actingAsAdmin()->getJson('/api/admin/categories')
            ->assertOk()
            ->assertJsonCount(8, 'data')
            ->assertJsonPath('data.0.slug', 'cpu')
            ->assertJsonPath('data.0.products_count', 8);
    }

    public function test_only_display_fields_can_be_changed(): void
    {
        $category = Category::where('slug', 'gpu')->first();

        $this->actingAsAdmin()->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'Card màn hình', 'description' => 'VGA', 'sort_order' => 9, 'slug' => 'vga',
        ])->assertOk()->assertJsonPath('data.name', 'Card màn hình');

        $category->refresh();
        $this->assertSame('gpu', $category->slug);
        $this->assertSame(9, $category->sort_order);
    }

    public function test_update_is_validated(): void
    {
        $category = Category::where('slug', 'gpu')->first();

        $this->actingAsAdmin()->putJson("/api/admin/categories/{$category->id}", ['name' => '', 'sort_order' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'sort_order']);
    }

    public function test_categories_cannot_be_created_or_deleted(): void
    {
        $category = Category::where('slug', 'gpu')->first();

        $this->actingAsAdmin()->postJson('/api/admin/categories', ['name' => 'Keyboard'])->assertStatus(405);
        $this->deleteJson("/api/admin/categories/{$category->id}")->assertStatus(405);
    }

    public function test_spec_schema_describes_the_product_form(): void
    {
        $fields = collect($this->actingAsAdmin()->getJson('/api/admin/categories/cooler/spec-schema')
            ->assertOk()->json('data.fields'))->keyBy('key');

        $this->assertSame('enum', $fields['type']['type']);
        $this->assertSame(['value' => 'aio', 'label' => 'Tản nước AIO'], $fields['type']['options'][1]);
        $this->assertSame('enum_list', $fields['supported_sockets']['type']);
        $this->assertSame(['when' => ['type' => 'air']], $fields['height_mm']['required']);
        $this->assertSame('mm', $fields['height_mm']['unit']);
    }
}
