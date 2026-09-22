<?php

namespace Tests\Unit\Support;

use App\Support\Hardware\SpecFormatter;
use Tests\TestCase;

class SpecFormatterTest extends TestCase
{
    private function formatter(): SpecFormatter
    {
        return $this->app->make(SpecFormatter::class);
    }

    public function test_rows_follow_config_order_with_labels_units_and_highlights(): void
    {
        $rows = $this->formatter()->format('cpu', [
            'tdp' => 65, 'socket' => 'am5', 'cores' => 6, 'threads' => 12,
            'has_integrated_graphics' => true, 'includes_cooler' => false, 'performance_tier' => 62,
        ]);

        $this->assertSame(['socket', 'cores', 'threads', 'tdp', 'has_integrated_graphics', 'includes_cooler', 'performance_tier'],
            array_column($rows, 'key'));
        $this->assertSame(['key' => 'socket', 'label' => 'Socket', 'value' => 'am5', 'display' => 'AM5', 'highlight' => true], $rows[0]);
        $this->assertSame('65 W', $rows[3]['display']);
        $this->assertSame('Có', $rows[4]['display']);
        $this->assertSame('Không', $rows[5]['display']);
    }

    public function test_enum_lists_and_thousands_separator(): void
    {
        $case = $this->formatter()->format('case', [
            'supported_form_factors' => ['atx', 'matx'], 'max_gpu_length_mm' => 365,
            'max_cooler_height_mm' => 165, 'supported_psu_form_factors' => ['atx'],
        ]);
        $storage = $this->formatter()->format('storage', ['interface' => 'nvme', 'form' => 'm2', 'capacity_gb' => 2000]);

        $this->assertSame('ATX, Micro-ATX', $case[0]['display']);
        $this->assertSame('2.000 GB', $storage[2]['display']);
    }

    public function test_absent_optional_specs_are_skipped(): void
    {
        $rows = $this->formatter()->format('cooler', [
            'type' => 'aio', 'supported_sockets' => ['am5'], 'max_tdp' => 250, 'radiator_mm' => 240,
        ]);

        $this->assertNotContains('height_mm', array_column($rows, 'key'));
        $this->assertSame('Tản nước AIO', $rows[0]['display']);
    }
}
