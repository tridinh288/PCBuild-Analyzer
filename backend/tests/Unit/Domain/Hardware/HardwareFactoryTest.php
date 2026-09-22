<?php

namespace Tests\Unit\Domain\Hardware;

use App\Domain\Hardware\Components\Cooler;
use App\Domain\Hardware\Components\Cpu;
use App\Domain\Hardware\Components\PcCase;
use App\Domain\Hardware\Components\Ram;
use App\Domain\Hardware\Components\Storage;
use App\Domain\Hardware\HardwareFactory;
use InvalidArgumentException;
use Tests\Unit\Domain\DomainTestCase;

class HardwareFactoryTest extends DomainTestCase
{
    public function test_it_maps_every_config_category_to_a_class_with_the_same_slug(): void
    {
        $factory = new HardwareFactory;

        $this->assertSame(array_keys(self::hardwareConfig()['categories']), $factory->categories());

        foreach ($factory->categories() as $category) {
            $this->assertSame($category, $this->component($category)::category());
        }
    }

    public function test_it_exposes_identity_and_typed_specs(): void
    {
        $cpu = (new HardwareFactory)->make('cpu', [
            'id' => 7, 'name' => 'AMD Ryzen 5 7600', 'price' => 5_290_000, 'slug' => 'amd-ryzen-5-7600', 'brand' => 'AMD',
            'specs' => ['socket' => 'am5', 'cores' => 6, 'threads' => 12, 'tdp' => 65,
                'has_integrated_graphics' => true, 'includes_cooler' => false, 'performance_tier' => 62],
        ]);

        $this->assertInstanceOf(Cpu::class, $cpu);
        $this->assertSame([7, 'AMD Ryzen 5 7600', 5_290_000, 'amd-ryzen-5-7600', 'AMD'],
            [$cpu->id(), $cpu->name(), $cpu->price(), $cpu->slug(), $cpu->brand()]);
        $this->assertSame('am5', $cpu->socket());
        $this->assertSame(65, $cpu->tdp());
        $this->assertTrue($cpu->hasIntegratedGraphics());
        $this->assertFalse($cpu->includesCooler());
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new HardwareFactory)->make('keyboard', ['id' => 1, 'name' => 'x', 'price' => 1, 'specs' => []]);
    }

    public function test_ram_totals_count_kits(): void
    {
        /** @var Ram $ram */
        $ram = $this->component('ram', ['capacity_gb' => 16, 'modules' => 2]);

        $this->assertSame(4, $ram->totalModules(2));
        $this->assertSame(32, $ram->totalCapacityGb(2));
    }

    public function test_case_form_factor_helpers(): void
    {
        /** @var PcCase $case */
        $case = $this->component('case', ['supported_form_factors' => ['itx'], 'supported_psu_form_factors' => ['sfx']]);

        $this->assertTrue($case->supportsFormFactor('itx'));
        $this->assertFalse($case->supportsFormFactor('atx'));
        $this->assertTrue($case->supportsPsuFormFactor('sfx'));
        $this->assertFalse($case->supportsPsuFormFactor('atx'));
    }

    public function test_storage_slot_type(): void
    {
        /** @var Storage $nvme */
        $nvme = $this->component('storage', ['interface' => 'nvme', 'form' => 'm2']);
        /** @var Storage $sata */
        $sata = $this->component('storage', ['interface' => 'sata', 'form' => '2_5']);

        $this->assertTrue($nvme->isM2());
        $this->assertFalse($nvme->isSata());
        $this->assertTrue($sata->isSata());
        $this->assertFalse($sata->isM2());
    }

    public function test_aio_cooler_has_no_height(): void
    {
        /** @var Cooler $aio */
        $aio = (new HardwareFactory)->make('cooler', ['id' => 1, 'name' => 'AIO', 'price' => 1,
            'specs' => ['type' => 'aio', 'supported_sockets' => ['am5'], 'max_tdp' => 250, 'radiator_mm' => 240]]);

        $this->assertFalse($aio->isAir());
        $this->assertNull($aio->heightMm());
        $this->assertSame(240, $aio->radiatorMm());
        $this->assertTrue($aio->supportsSocket('am5'));
        $this->assertFalse($aio->supportsSocket('lga1700'));
    }
}
