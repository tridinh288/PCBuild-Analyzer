<?php

namespace Tests\Feature\Services;

use App\Domain\Hardware\Components\Cpu;
use App\Models\Build;
use App\Models\Product;
use App\Services\BuildConfigurationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BuildConfigurationFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function factory(): BuildConfigurationFactory
    {
        return $this->app->make(BuildConfigurationFactory::class);
    }

    private function id(string $name): int
    {
        return Product::where('name', $name)->value('id');
    }

    public function test_from_build_includes_every_item_with_its_quantity(): void
    {
        $build = Build::where('slug', 'lap-trinh-intel-hai-o-cung')->first();

        $config = $this->factory()->fromBuild($build);

        $this->assertInstanceOf(Cpu::class, $config->cpu());
        $this->assertSame('Intel Core i5-13400', $config->cpu()->name());
        $this->assertSame(2, $config->quantityOf('storage'));
        $this->assertSame([], $config->missingRequiredSlots());
    }

    public function test_from_build_keeps_deactivated_products(): void
    {
        $build = Build::where('slug', 'gaming-1080p-am5')->first();
        Product::whereKey($this->id('NZXT H5 Flow'))->update(['is_active' => false]);

        $this->assertSame('NZXT H5 Flow', $this->factory()->fromBuild($build)->pcCase()->name());
    }

    public function test_from_selection_accepts_every_input_shape(): void
    {
        $resolved = $this->factory()->fromSelection([
            'cpu' => $this->id('AMD Ryzen 5 7600'),
            'ram' => ['id' => $this->id('Kingston FURY Beast 16GB (2x8GB) DDR5 5600MHz'), 'quantity' => 2],
            'storage' => [$this->id('WD Blue SN580 1TB NVMe'), ['id' => $this->id('Crucial MX500 1TB SATA'), 'quantity' => 2]],
        ]);

        $config = $resolved->configuration;
        $this->assertSame([], $resolved->missing);
        $this->assertSame('AMD Ryzen 5 7600', $config->cpu()->name());
        $this->assertSame(2, $config->quantityOf('ram'));
        $this->assertSame(3, $config->quantityOf('storage'));
    }

    public function test_from_selection_reports_unknown_inactive_and_wrong_category_ids_as_missing(): void
    {
        $inactive = $this->id('Deepcool PF450 450W');
        Product::whereKey($inactive)->update(['is_active' => false]);
        $gpuInCpuSlot = $this->id('ASUS Dual GeForce RTX 4060 OC 8GB');

        $resolved = $this->factory()->fromSelection([
            'cpu' => $gpuInCpuSlot,
            'psu' => $inactive,
            'case' => 999_999,
            'motherboard' => $this->id('Gigabyte B650M GAMING X AX'),
        ]);

        $this->assertSame([
            ['category' => 'cpu', 'id' => $gpuInCpuSlot],
            ['category' => 'psu', 'id' => $inactive],
            ['category' => 'case', 'id' => 999_999],
        ], $resolved->missing);
        $this->assertTrue($resolved->configuration->has('motherboard'));
        $this->assertFalse($resolved->configuration->has('cpu'));
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory()->fromSelection(['keyboard' => 1]);
    }
}
