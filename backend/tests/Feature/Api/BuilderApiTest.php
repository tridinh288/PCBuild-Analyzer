<?php

namespace Tests\Feature\Api;

use App\Models\Build;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function id(string $name): int
    {
        return Product::where('name', $name)->value('id');
    }

    public function test_options_mark_ddr4_ram_incompatible_with_a_ddr5_motherboard(): void
    {
        $response = $this->postJson('/api/builder/options', [
            'category' => 'ram',
            'selected' => ['motherboard' => $this->id('Gigabyte B650M GAMING X AX')],
        ])->assertOk();

        $byName = collect($response->json('data'))->keyBy('product.name');
        $ddr4 = $byName['Kingston FURY Beast 16GB (2x8GB) DDR4 3200MHz'];

        $this->assertSame('incompatible', $ddr4['status']);
        $this->assertSame('motherboard_ram_type', $ddr4['issues'][0]['rule']);
        $this->assertSame('Bo mạch chủ hỗ trợ DDR5, RAM này là DDR4.', $ddr4['issues'][0]['message']);
        $this->assertSame('compatible', $byName['G.Skill Trident Z5 RGB 32GB (2x16GB) DDR5 6000MHz']['status']);
        $response->assertJsonPath('meta.total', 5)->assertJsonPath('meta.missing', []);
    }

    public function test_options_with_compatible_only_and_filters(): void
    {
        $response = $this->postJson('/api/builder/options', [
            'category' => 'ram',
            'selected' => ['motherboard' => $this->id('Gigabyte B650M GAMING X AX')],
            'filters' => ['capacity_gb' => 32],
            'compatible_only' => true,
            'sort' => 'price_asc',
        ])->assertOk();

        $this->assertSame(['G.Skill Trident Z5 RGB 32GB (2x16GB) DDR5 6000MHz'],
            collect($response->json('data'))->pluck('product.name')->all());
    }

    public function test_options_report_missing_ids(): void
    {
        $this->postJson('/api/builder/options', ['category' => 'gpu', 'selected' => ['case' => 999999]])
            ->assertOk()
            ->assertJsonPath('meta.missing', [['category' => 'case', 'id' => 999999]]);
    }

    public function test_analyze_a_custom_selection_with_every_input_shape(): void
    {
        $template = Build::where('slug', 'gaming-1080p-am5')->first();
        $selected = $template->items->mapWithKeys(fn ($item) => [$item->product->category->slug => $item->product_id])->all();
        $selected['ram'] = ['id' => $selected['ram'], 'quantity' => 2];
        $selected['storage'] = [$selected['storage'], ['id' => $this->id('Crucial MX500 1TB SATA'), 'quantity' => 1]];

        $response = $this->postJson('/api/builder/analyze', ['selected' => $selected, 'profile' => 'gaming'])->assertOk();

        // Template 26.33M + one more RAM kit (3.19M) + a SATA SSD (1.69M)
        $response->assertJsonPath('data.price.total', 31_210_000)
            ->assertJsonPath('data.items.0.category', 'cpu')
            ->assertJsonPath('data.items.0.product.name', 'AMD Ryzen 5 7600')
            ->assertJsonPath('data.items.2.quantity', 2)
            ->assertJsonPath('data.compatibility.status', 'compatible')
            ->assertJsonPath('meta.profile', 'gaming')
            ->assertJsonPath('data.is_complete', true);
    }

    public function test_analyze_returns_missing_ids_for_deleted_or_inactive_products(): void
    {
        $inactive = $this->id('NZXT H5 Flow');
        Product::whereKey($inactive)->update(['is_active' => false]);

        $response = $this->postJson('/api/builder/analyze', [
            'selected' => ['cpu' => $this->id('AMD Ryzen 5 7600'), 'case' => $inactive, 'gpu' => 999999],
        ])->assertOk();

        $response->assertJsonPath('meta.missing', [
            ['category' => 'case', 'id' => $inactive],
            ['category' => 'gpu', 'id' => 999999],
        ])->assertJsonPath('meta.profile', 'general_use')
            ->assertJsonPath('data.is_complete', false);
        $this->assertContains('case', $response->json('data.missing_slots'));
    }

    public function test_analyze_accepts_an_empty_selection(): void
    {
        $response = $this->postJson('/api/builder/analyze', ['selected' => (object) []])
            ->assertOk()
            ->assertJsonPath('data.price.total', 0);

        // Map-like fields stay JSON objects when empty, so the frontend sees one type.
        $this->assertStringContainsString('"by_category":{}', $response->getContent());
        $this->assertStringContainsString('"breakdown":{}', $response->getContent());
        $this->assertStringContainsString('"details":{}', $response->getContent());
        // Vietnamese is sent as UTF-8, not \u escapes.
        $this->assertStringContainsString('Chưa đủ linh kiện để kiểm tra.', $response->getContent());
    }

    public function test_invalid_input_is_rejected_with_422(): void
    {
        $invalid = [
            ['selected' => ['keyboard' => 1]],
            ['selected' => ['cpu' => 'abc']],
            ['selected' => ['cpu' => [1, 2]]],
            ['selected' => ['cpu' => ['id' => 1, 'quantity' => 2]]],
            ['selected' => ['ram' => ['id' => 1, 'quantity' => 5]]],
            ['selected' => [1, 2, 3]],
            ['selected' => ['cpu' => 1], 'profile' => 'mining'],
            [],
        ];

        foreach ($invalid as $body) {
            $this->postJson('/api/builder/analyze', $body)->assertUnprocessable()->assertJsonPath('success', false);
        }

        $this->postJson('/api/builder/options', ['category' => 'keyboard', 'selected' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->postJson('/api/builder/options', ['category' => 'cpu', 'selected' => [], 'filters' => ['socket' => 'am9']])
            ->assertUnprocessable()->assertJsonValidationErrors('filters.socket');
    }
}
