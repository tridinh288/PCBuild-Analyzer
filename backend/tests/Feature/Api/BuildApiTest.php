<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_builds_list_with_filters_sort_and_pagination(): void
    {
        $response = $this->getJson('/api/builds?purpose=gaming&price_max=40000000&sort=price_asc&per_page=2')->assertOk();

        $this->assertSame(['gaming-pho-thong-am4', 'gaming-1080p-am5'], array_column($response->json('data'), 'slug'));
        $response->assertJsonPath('data.0.total_price', 14_830_000)
            ->assertJsonPath('data.0.purpose_label', 'Chơi game')
            ->assertJsonPath('meta.total', 4)
            ->assertJsonMissingPath('data.0.items');
    }

    public function test_featured_filter(): void
    {
        $response = $this->getJson('/api/builds?featured=1')->assertOk();

        $this->assertSame([true], array_values(array_unique(array_column($response->json('data'), 'is_featured'))));
    }

    public function test_build_list_filters_are_validated(): void
    {
        $this->getJson('/api/builds?purpose=mining')->assertUnprocessable()->assertJsonValidationErrors('purpose');
        $this->getJson('/api/builds?price_min=-1')->assertUnprocessable()->assertJsonValidationErrors('price_min');
    }

    public function test_build_detail_includes_items_and_total(): void
    {
        $response = $this->getJson('/api/builds/lap-trinh-intel-hai-o-cung')->assertOk();

        $response->assertJsonPath('data.total_price', 21_330_000)
            ->assertJsonPath('data.items.0.category', 'cpu')
            ->assertJsonPath('data.items.0.product.name', 'Intel Core i5-13400');
        $this->assertCount(7, $response->json('data.items'));
    }

    public function test_unknown_build_returns_404(): void
    {
        $this->getJson('/api/builds/does-not-exist')->assertNotFound()->assertJsonPath('success', false);
    }

    public function test_analysis_defaults_to_the_build_purpose(): void
    {
        $response = $this->getJson('/api/builds/gaming-1440p-nguon-sat-gioi-han/analysis')->assertOk();

        $response->assertJsonPath('meta.profile', 'gaming')
            ->assertJsonPath('data.performance.profile', 'gaming')
            ->assertJsonPath('data.compatibility.status', 'warning')
            ->assertJsonPath('data.compatibility.warnings', 1)
            ->assertJsonPath('data.compatibility.by_category.psu', ['psu_wattage'])
            ->assertJsonPath('data.power.estimated_watts', 392)
            ->assertJsonPath('data.power.recommended_psu_watts', 550)
            ->assertJsonPath('data.power.selected_psu_watts', 450)
            ->assertJsonPath('data.price.total', 31_630_000)
            ->assertJsonPath('data.is_complete', true);
    }

    public function test_analysis_profile_can_be_switched(): void
    {
        $gaming = $this->getJson('/api/builds/lap-trinh-am5-khong-card-roi/analysis?profile=gaming')->json('data.performance.score');
        $programming = $this->getJson('/api/builds/lap-trinh-am5-khong-card-roi/analysis')->json('data.performance.score');

        $this->assertLessThan($programming, $gaming);
    }

    public function test_analysis_rejects_unknown_profile(): void
    {
        $this->getJson('/api/builds/gaming-1080p-am5/analysis?profile=mining')
            ->assertUnprocessable()->assertJsonValidationErrors('profile');
    }
}
