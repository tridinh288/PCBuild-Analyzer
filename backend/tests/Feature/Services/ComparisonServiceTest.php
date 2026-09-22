<?php

namespace Tests\Feature\Services;

use App\Enums\BuildPurpose;
use App\Models\Build;
use App\Models\Product;
use App\Services\ComparisonService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparisonServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_compares_a_template_with_a_custom_configuration_using_one_profile(): void
    {
        $template = Build::where('slug', 'gaming-1080p-am5')->first();
        $selection = $template->items->mapWithKeys(fn ($item) => [$item->product->category->slug => $item->product_id])->all();
        $selection['cpu'] = Product::where('name', 'AMD Ryzen 7 7700')->value('id');

        $result = $this->app->make(ComparisonService::class)->compare([
            ['type' => 'template', 'slug' => 'gaming-1080p-am5'],
            ['type' => 'custom', 'selected' => $selection],
        ], BuildPurpose::Gaming);

        $comparison = $result['comparison'];
        $this->assertSame(['Gaming 1080p AM5', 'Cấu hình tùy chỉnh 1'], array_column($comparison->configurations, 'label'));
        $this->assertSame(['gaming', 'gaming'], array_column($comparison->configurations, 'profile'));

        $changes = $comparison->differences[0]['changes'];
        $this->assertSame(['type' => 'slot', 'category' => 'cpu', 'from' => ['AMD Ryzen 5 7600'], 'to' => ['AMD Ryzen 7 7700']], $changes[0]);
        $this->assertContains(['type' => 'metric', 'metric' => 'total_price', 'from' => 26_330_000, 'to' => 28_530_000, 'delta' => 2_200_000], $changes);
        $this->assertSame([[], []], $result['missing']);
    }

    public function test_unknown_template_is_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->app->make(ComparisonService::class)->compare([
            ['type' => 'template', 'slug' => 'gaming-1080p-am5'],
            ['type' => 'template', 'slug' => 'does-not-exist'],
        ]);
    }
}
