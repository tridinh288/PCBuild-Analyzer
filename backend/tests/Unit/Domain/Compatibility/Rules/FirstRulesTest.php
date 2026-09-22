<?php

namespace Tests\Unit\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Rules\CpuMotherboardSocketRule;
use App\Domain\Compatibility\Rules\MotherboardCaseFormFactorRule;
use App\Domain\Compatibility\Rules\MotherboardRamCapacityRule;
use App\Domain\Compatibility\Rules\MotherboardRamTypeRule;
use App\Enums\CompatibilityStatus;
use Tests\Unit\Domain\DomainTestCase;

/**
 * The first four MVP rules: compatible, incompatible and skipped case for each (spec section 30).
 */
class FirstRulesTest extends DomainTestCase
{
    public function test_cpu_motherboard_socket(): void
    {
        $rule = new CpuMotherboardSocketRule($this->labels());

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule, $this->configuration(
            $this->component('cpu', ['socket' => 'am5']), $this->component('motherboard', ['socket' => 'am5'])));

        $result = $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule, $this->configuration(
            $this->component('cpu', ['socket' => 'am5']), $this->component('motherboard', ['socket' => 'lga1700'])));
        $this->assertSame('CPU dùng socket AM5 nhưng bo mạch chủ dùng socket LGA1700.', $result->message);
        $this->assertSame(['cpu_socket' => 'am5', 'motherboard_socket' => 'lga1700'], $result->details);

        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($this->component('cpu')));
    }

    public function test_motherboard_ram_type(): void
    {
        $rule = new MotherboardRamTypeRule($this->labels());

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule, $this->configuration(
            $this->component('motherboard', ['ram_type' => 'ddr5']), $this->component('ram', ['ram_type' => 'ddr5'])));

        $result = $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule, $this->configuration(
            $this->component('motherboard', ['ram_type' => 'ddr5']), $this->component('ram', ['ram_type' => 'ddr4'])));
        $this->assertSame('Bo mạch chủ hỗ trợ DDR5, RAM này là DDR4.', $result->message);

        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($this->component('ram')));
    }

    public function test_ram_capacity_within_limits(): void
    {
        $this->assertRuleStatus(CompatibilityStatus::Compatible, new MotherboardRamCapacityRule, $this->configuration(
            $this->component('motherboard', ['ram_slots' => 4, 'max_ram_gb' => 128]),
            [$this->component('ram', ['modules' => 2, 'capacity_gb' => 64]), 2],
        ));
    }

    public function test_ram_quantity_exceeding_slots_is_incompatible(): void
    {
        $result = $this->assertRuleStatus(CompatibilityStatus::Incompatible, new MotherboardRamCapacityRule, $this->configuration(
            $this->component('motherboard', ['ram_slots' => 2, 'max_ram_gb' => 96]),
            [$this->component('ram', ['modules' => 2, 'capacity_gb' => 16]), 2],
        ));

        $this->assertSame('Cần 4 khe RAM nhưng bo mạch chủ chỉ có 2 khe.', $result->message);
        $this->assertSame(4, $result->details['modules']);
    }

    public function test_total_ram_exceeding_max_capacity_is_incompatible(): void
    {
        $result = $this->assertRuleStatus(CompatibilityStatus::Incompatible, new MotherboardRamCapacityRule, $this->configuration(
            $this->component('motherboard', ['ram_slots' => 4, 'max_ram_gb' => 64]),
            [$this->component('ram', ['modules' => 2, 'capacity_gb' => 64]), 2],
        ));

        $this->assertSame('Tổng dung lượng 128GB vượt mức tối đa 64GB của bo mạch chủ.', $result->message);
    }

    public function test_ram_capacity_skipped_without_motherboard(): void
    {
        $this->assertRuleStatus(CompatibilityStatus::Skipped, new MotherboardRamCapacityRule,
            $this->configuration($this->component('ram')));
    }

    public function test_motherboard_case_form_factor(): void
    {
        $rule = new MotherboardCaseFormFactorRule($this->labels());

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule, $this->configuration(
            $this->component('motherboard', ['form_factor' => 'matx']),
            $this->component('case', ['supported_form_factors' => ['atx', 'matx']])));

        $result = $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule, $this->configuration(
            $this->component('motherboard', ['form_factor' => 'atx']),
            $this->component('case', ['supported_form_factors' => ['itx']])));
        $this->assertSame('Vỏ case không hỗ trợ bo mạch chủ ATX (chỉ hỗ trợ: Mini-ITX).', $result->message);

        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($this->component('case')));
    }
}
