<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_compares_templates_and_a_custom_configuration(): void
    {
        $response = $this->postJson('/api/compare', [
            'configurations' => [
                ['type' => 'template', 'slug' => 'gaming-1080p-am5'],
                ['type' => 'template', 'slug' => 'gaming-2k-ryzen-7-7800x3d'],
                ['type' => 'custom', 'label' => 'Của tôi', 'selected' => [
                    'cpu' => Product::where('name', 'AMD Ryzen 7 7700')->value('id'),
                    'gpu' => 999999,
                ]],
            ],
            'profile' => 'gaming',
        ])->assertOk();

        $response->assertJsonPath('meta.profile', 'gaming')
            ->assertJsonPath('meta.missing', [[], [], [['category' => 'gpu', 'id' => 999999]]])
            ->assertJsonPath('data.configurations.0.label', 'Gaming 1080p AM5')
            ->assertJsonPath('data.configurations.2.label', 'Của tôi')
            ->assertJsonPath('data.configurations.1.total_price', 48_720_000)
            ->assertJsonPath('data.slots.0.category', 'cpu')
            ->assertJsonPath('data.slots.0.values', [['AMD Ryzen 5 7600'], ['AMD Ryzen 7 7800X3D'], ['AMD Ryzen 7 7700']]);

        $this->assertCount(2, $response->json('data.differences'));
        $this->assertContains(
            ['type' => 'metric', 'metric' => 'total_price', 'from' => 26_330_000, 'to' => 48_720_000, 'delta' => 22_390_000],
            $response->json('data.differences.0.changes'),
        );
        $this->assertStringNotContainsString('winner', $response->getContent());
    }

    public function test_unknown_template_returns_404(): void
    {
        $this->postJson('/api/compare', ['configurations' => [
            ['type' => 'template', 'slug' => 'gaming-1080p-am5'],
            ['type' => 'template', 'slug' => 'does-not-exist'],
        ]])->assertNotFound()->assertJsonPath('success', false);
    }

    public function test_input_is_validated(): void
    {
        $one = ['configurations' => [['type' => 'template', 'slug' => 'gaming-1080p-am5']]];
        $four = ['configurations' => array_fill(0, 4, ['type' => 'template', 'slug' => 'gaming-1080p-am5'])];
        $noSlug = ['configurations' => [['type' => 'template'], ['type' => 'template', 'slug' => 'x']]];
        $noSelection = ['configurations' => [['type' => 'custom'], ['type' => 'template', 'slug' => 'x']]];
        $badSelection = ['configurations' => [['type' => 'custom', 'selected' => ['cpu' => 'abc']], ['type' => 'template', 'slug' => 'x']]];

        $this->postJson('/api/compare', $one)->assertUnprocessable()->assertJsonValidationErrors('configurations');
        $this->postJson('/api/compare', $four)->assertUnprocessable()->assertJsonValidationErrors('configurations');
        $this->postJson('/api/compare', $noSlug)->assertUnprocessable()->assertJsonValidationErrors('configurations.0.slug');
        $this->postJson('/api/compare', $noSelection)->assertUnprocessable()->assertJsonValidationErrors('configurations.0.selected');
        $this->postJson('/api/compare', $badSelection)->assertUnprocessable()->assertJsonValidationErrors('configurations.0.selected');
    }
}
