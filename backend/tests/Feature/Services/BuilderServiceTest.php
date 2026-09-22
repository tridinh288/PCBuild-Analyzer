<?php

namespace Tests\Feature\Services;

use App\Enums\BuildPurpose;
use App\Enums\CompatibilityStatus;
use App\Models\Product;
use App\Services\BuilderOption;
use App\Services\BuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function service(): BuilderService
    {
        return $this->app->make(BuilderService::class);
    }

    private function id(string $name): int
    {
        return Product::where('name', $name)->value('id');
    }

    /**
     * @param  list<BuilderOption>  $options
     * @return array<string, CompatibilityStatus>
     */
    private function statuses(array $options): array
    {
        return collect($options)->mapWithKeys(fn (BuilderOption $o) => [$o->product->name => $o->status])->all();
    }

    public function test_ddr4_ram_is_incompatible_with_a_ddr5_motherboard(): void
    {
        $result = $this->service()->optionsFor('ram', ['motherboard' => $this->id('Gigabyte B650M GAMING X AX')]);
        $statuses = $this->statuses($result['options']);

        $this->assertCount(5, $result['options']);
        $this->assertSame(CompatibilityStatus::Incompatible, $statuses['Kingston FURY Beast 16GB (2x8GB) DDR4 3200MHz']);
        $this->assertSame(CompatibilityStatus::Compatible, $statuses['G.Skill Trident Z5 RGB 32GB (2x16GB) DDR5 6000MHz']);

        $ddr4 = collect($result['options'])->first(fn ($o) => $o->status === CompatibilityStatus::Incompatible);
        $this->assertSame('motherboard_ram_type', $ddr4->issues[0]->rule);
    }

    public function test_compatible_only_hides_incompatible_candidates(): void
    {
        $result = $this->service()->optionsFor('ram', ['motherboard' => $this->id('Gigabyte B650M GAMING X AX')], compatibleOnly: true);

        $this->assertCount(3, $result['options']);
        $this->assertNotContains(CompatibilityStatus::Incompatible, array_map(fn ($o) => $o->status, $result['options']));
    }

    public function test_candidate_is_not_blamed_for_an_existing_mismatch(): void
    {
        $result = $this->service()->optionsFor('ram', [
            'cpu' => $this->id('Intel Core i5-13400'),                 // LGA1700 …
            'motherboard' => $this->id('Gigabyte B650M GAMING X AX'),  // … on an AM5 board
        ]);

        $this->assertSame(CompatibilityStatus::Compatible,
            $this->statuses($result['options'])['G.Skill Trident Z5 RGB 32GB (2x16GB) DDR5 6000MHz']);
    }

    public function test_gpu_candidates_are_checked_against_case_and_psu(): void
    {
        $statuses = $this->statuses($this->service()->optionsFor('gpu', [
            'cpu' => $this->id('AMD Ryzen 7 7700'),
            'case' => $this->id('Cooler Master MasterBox NR200P'),
            'psu' => $this->id('Deepcool PF450 450W'),
        ])['options']);

        $this->assertSame(CompatibilityStatus::Incompatible, $statuses['Gigabyte GeForce RTX 4080 SUPER GAMING OC 16G']); // 342 mm > 330 mm
        $this->assertSame(CompatibilityStatus::Warning, $statuses['Sapphire PULSE Radeon RX 7700 XT 12GB']);           // PSU below recommendation
        $this->assertSame(CompatibilityStatus::Compatible, $statuses['Gigabyte GeForce RTX 3050 WINDFORCE OC 6G']);
    }

    public function test_cpu_without_igpu_is_not_red_before_a_gpu_is_chosen(): void
    {
        $statuses = $this->statuses($this->service()->optionsFor('cpu', [])['options']);

        $this->assertSame(CompatibilityStatus::Compatible, $statuses['Intel Core i3-12100F']);
    }

    public function test_filters_sort_selected_flag_and_missing_ids(): void
    {
        $selected = $this->id('Crucial MX500 1TB SATA');

        $result = $this->service()->optionsFor('storage', ['storage' => [$selected], 'psu' => 999_999],
            ['interface' => 'sata'], sort: 'price_asc');

        $this->assertSame(['Kingston A400 480GB SATA', 'Seagate BarraCuda 2TB HDD', 'Crucial MX500 1TB SATA'],
            array_map(fn ($o) => $o->product->name, $result['options']));
        $this->assertSame([false, false, true], array_map(fn ($o) => $o->selected, $result['options']));
        $this->assertSame([['category' => 'psu', 'id' => 999_999]], $result['missing']);
    }

    public function test_analyze_a_custom_selection(): void
    {
        $result = $this->service()->analyze([
            'cpu' => $this->id('AMD Ryzen 5 7600'),
            'gpu' => 999_999,
        ], BuildPurpose::Gaming);

        $this->assertSame(BuildPurpose::Gaming, $result['analysis']->performance->profile);
        $this->assertSame(5_290_000, $result['analysis']->price->total);
        $this->assertContains('motherboard', $result['analysis']->missingSlots);
        $this->assertSame([['category' => 'gpu', 'id' => 999_999]], $result['missing']);
    }
}
