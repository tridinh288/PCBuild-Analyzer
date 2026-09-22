<?php

namespace Tests\Unit\Domain\Configuration;

use App\Domain\Configuration\BuildConfiguration;
use InvalidArgumentException;
use Tests\Unit\Domain\DomainTestCase;

class BuildConfigurationTest extends DomainTestCase
{
    public function test_with_returns_a_new_instance_and_leaves_the_original_unchanged(): void
    {
        $original = BuildConfiguration::empty($this->slotRules());
        $cpu = $this->component('cpu');

        $changed = $original->with($cpu);

        $this->assertNotSame($original, $changed);
        $this->assertFalse($original->has('cpu'));
        $this->assertSame($cpu, $changed->get('cpu'));
    }

    public function test_without_returns_a_new_instance(): void
    {
        $original = $this->configuration($this->component('cpu'));

        $changed = $original->without('cpu');

        $this->assertTrue($original->has('cpu'));
        $this->assertFalse($changed->has('cpu'));
    }

    public function test_single_slot_is_replaced(): void
    {
        $second = $this->component('cpu');

        $config = $this->configuration($this->component('cpu'), $second);

        $this->assertCount(1, $config->items('cpu'));
        $this->assertSame($second, $config->cpu());
    }

    public function test_storage_accepts_several_products_and_merges_the_same_product(): void
    {
        $nvme = $this->component('storage');
        $sata = $this->component('storage', ['interface' => 'sata', 'form' => '2_5']);

        $config = $this->configuration($nvme, $sata, [$sata, 2]);

        $this->assertCount(2, $config->items('storage'));
        $this->assertSame(4, $config->quantityOf('storage'));
    }

    public function test_without_can_remove_one_product_from_a_multi_product_slot(): void
    {
        $nvme = $this->component('storage');
        $sata = $this->component('storage', ['interface' => 'sata', 'form' => '2_5']);

        $config = $this->configuration($nvme, $sata)->without('storage', $nvme->id());

        $this->assertSame([$sata], array_map(fn ($item) => $item->component, $config->items('storage')));
    }

    public function test_ram_quantity_counts_kits(): void
    {
        $config = $this->configuration([$this->component('ram'), 2]);

        $this->assertSame(2, $config->quantityOf('ram'));
    }

    public function test_quantity_is_rejected_for_single_unit_slots(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration([$this->component('cpu'), 2]);
    }

    public function test_quantity_below_one_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration([$this->component('ram'), 0]);
    }

    public function test_typed_accessors_return_null_for_empty_slots(): void
    {
        $config = BuildConfiguration::empty($this->slotRules());

        $this->assertNull($config->cpu());
        $this->assertNull($config->pcCase());
        $this->assertTrue($config->isEmpty());
    }

    public function test_all_items_are_in_slot_order_regardless_of_selection_order(): void
    {
        $config = $this->configuration($this->component('cooler'), $this->component('cpu'), $this->component('ram'));

        $this->assertSame(['cpu', 'ram', 'cooler'], array_map(fn ($item) => $item->category(), $config->allItems()));
    }

    public function test_empty_configuration_misses_the_unconditional_slots(): void
    {
        $missing = BuildConfiguration::empty($this->slotRules())->missingRequiredSlots();

        $this->assertSame(['cpu', 'motherboard', 'ram', 'storage', 'psu', 'case'], $missing);
    }

    public function test_gpu_is_required_when_cpu_has_no_integrated_graphics(): void
    {
        $withIgpu = $this->configuration($this->component('cpu', ['has_integrated_graphics' => true]));
        $withoutIgpu = $this->configuration($this->component('cpu', ['has_integrated_graphics' => false]));

        $this->assertNotContains('gpu', $withIgpu->missingRequiredSlots());
        $this->assertContains('gpu', $withoutIgpu->missingRequiredSlots());
    }

    public function test_cooler_is_required_when_cpu_has_no_boxed_cooler(): void
    {
        $boxed = $this->configuration($this->component('cpu', ['includes_cooler' => true]));
        $tray = $this->configuration($this->component('cpu', ['includes_cooler' => false]));

        $this->assertNotContains('cooler', $boxed->missingRequiredSlots());
        $this->assertContains('cooler', $tray->missingRequiredSlots());
    }

    public function test_complete_configuration_has_no_missing_slots(): void
    {
        $this->assertSame([], $this->completeConfiguration()->missingRequiredSlots());
    }
}
