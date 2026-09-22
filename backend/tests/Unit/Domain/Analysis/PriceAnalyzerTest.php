<?php

namespace Tests\Unit\Domain\Analysis;

use App\Domain\Analysis\PriceAnalyzer;
use App\Domain\Configuration\BuildConfiguration;
use Tests\Unit\Domain\DomainTestCase;

class PriceAnalyzerTest extends DomainTestCase
{
    public function test_total_respects_quantity_and_categories_follow_slot_order(): void
    {
        $config = $this->configuration(
            $this->component('gpu', price: 12_000_000),
            [$this->component('ram', price: 1_500_000), 2],
            $this->component('cpu', price: 5_000_000),
            $this->component('storage', price: 1_000_000),
            [$this->component('storage', ['interface' => 'sata', 'form' => '2_5'], price: 500_000), 2],
        );

        $result = (new PriceAnalyzer)->analyze($config);

        $this->assertSame(22_000_000, $result->total);
        $this->assertSame(['cpu', 'ram', 'gpu', 'storage'], array_column($result->byCategory, 'category'));
        $this->assertSame([5_000_000, 3_000_000, 12_000_000, 2_000_000], array_column($result->byCategory, 'amount'));
    }

    public function test_percentages_are_rounded_to_one_decimal(): void
    {
        $config = $this->configuration(
            $this->component('cpu', price: 1_000_000),
            $this->component('gpu', price: 2_000_000),
        );

        $result = (new PriceAnalyzer)->analyze($config);

        $this->assertSame([33.3, 66.7], array_column($result->byCategory, 'percent'));
    }

    public function test_empty_configuration_costs_nothing(): void
    {
        $result = (new PriceAnalyzer)->analyze(BuildConfiguration::empty($this->slotRules()));

        $this->assertSame(0, $result->total);
        $this->assertSame([], $result->byCategory);
    }
}
