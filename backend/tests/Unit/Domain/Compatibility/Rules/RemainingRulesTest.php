<?php

namespace Tests\Unit\Domain\Compatibility\Rules;

use App\Domain\Analysis\PowerCalculator;
use App\Domain\Compatibility\Rules\CoolerCaseHeightRule;
use App\Domain\Compatibility\Rules\CoolerCpuSocketRule;
use App\Domain\Compatibility\Rules\CoolerCpuTdpRule;
use App\Domain\Compatibility\Rules\CpuCoolingRule;
use App\Domain\Compatibility\Rules\DisplayOutputRule;
use App\Domain\Compatibility\Rules\GpuCaseClearanceRule;
use App\Domain\Compatibility\Rules\MotherboardStorageSlotsRule;
use App\Domain\Compatibility\Rules\PsuCaseFormFactorRule;
use App\Domain\Compatibility\Rules\PsuWattageRule;
use App\Enums\CompatibilityStatus;
use Tests\Unit\Domain\DomainTestCase;

/**
 * The remaining MVP rules: compatible, problem and skipped case for each (spec section 30).
 */
class RemainingRulesTest extends DomainTestCase
{
    public function test_motherboard_storage_slots(): void
    {
        $rule = new MotherboardStorageSlotsRule;
        $board = fn () => $this->component('motherboard', ['m2_slots' => 2, 'sata_ports' => 2]);
        $nvme = $this->component('storage', ['interface' => 'nvme', 'form' => 'm2']);
        $sata = $this->component('storage', ['interface' => 'sata', 'form' => '2_5']);

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration($board(), [$nvme, 2], [$sata, 2]));

        $result = $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule,
            $this->configuration($board(), [$nvme, 3], [$sata, 3]));
        $this->assertSame('Cần 3 khe M.2 nhưng bo mạch chủ chỉ có 2 khe. Cần 3 cổng SATA nhưng bo mạch chủ chỉ có 2 cổng.',
            $result->message);

        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($nvme));
    }

    public function test_gpu_case_clearance(): void
    {
        $rule = new GpuCaseClearanceRule;
        $case = fn () => $this->component('case', ['max_gpu_length_mm' => 330]);

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration($case(), $this->component('gpu', ['length_mm' => 330])));
        $result = $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule,
            $this->configuration($case(), $this->component('gpu', ['length_mm' => 342])));
        $this->assertSame('Card đồ họa dài 342mm, vượt giới hạn 330mm của vỏ case.', $result->message);
        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($this->component('gpu')));
    }

    public function test_cooler_cpu_socket(): void
    {
        $rule = new CoolerCpuSocketRule($this->labels());
        $cooler = fn () => $this->component('cooler', ['supported_sockets' => ['am4', 'am5']]);

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration($cooler(), $this->component('cpu', ['socket' => 'am5'])));
        $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule,
            $this->configuration($cooler(), $this->component('cpu', ['socket' => 'lga1700'])));
        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($cooler()));
    }

    public function test_cooler_cpu_tdp_is_a_warning(): void
    {
        $rule = new CoolerCpuTdpRule;
        $cpu = fn () => $this->component('cpu', ['tdp' => 120]);

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration($cpu(), $this->component('cooler', ['max_tdp' => 120])));
        $result = $this->assertRuleStatus(CompatibilityStatus::Warning, $rule,
            $this->configuration($cpu(), $this->component('cooler', ['max_tdp' => 65])));
        $this->assertSame(['cooler'], $result->categories);
        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($cpu()));
    }

    public function test_cooler_case_height(): void
    {
        $rule = new CoolerCaseHeightRule;
        $case = fn () => $this->component('case', ['max_cooler_height_mm' => 155]);

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration($case(), $this->component('cooler', ['height_mm' => 155])));
        $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule,
            $this->configuration($case(), $this->component('cooler', ['height_mm' => 165])));
        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($case()));
    }

    public function test_cooler_case_height_does_not_apply_to_aio(): void
    {
        $aio = $this->component('cooler', ['type' => 'aio', 'height_mm' => null, 'radiator_mm' => 360]);

        $this->assertRuleStatus(CompatibilityStatus::Compatible, new CoolerCaseHeightRule,
            $this->configuration($this->component('case', ['max_cooler_height_mm' => 60]), $aio));
    }

    public function test_psu_case_form_factor(): void
    {
        $rule = new PsuCaseFormFactorRule($this->labels());
        $itxCase = fn () => $this->component('case', ['supported_psu_form_factors' => ['sfx']]);

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration($itxCase(), $this->component('psu', ['form_factor' => 'sfx'])));
        $result = $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule,
            $this->configuration($itxCase(), $this->component('psu', ['form_factor' => 'atx'])));
        $this->assertSame('Vỏ case không hỗ trợ nguồn ATX (chỉ hỗ trợ: SFX).', $result->message);
        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($itxCase()));
    }

    public function test_psu_wattage_bands(): void
    {
        $rule = new PsuWattageRule(new PowerCalculator(self::hardwareConfig()['power']));
        // 65 + 245 + 10 (2 modules) + 7 (NVMe) + 50 + 15 = 392 W estimated, 490 → 550 W recommended
        $parts = fn () => [
            $this->component('cpu', ['tdp' => 65]),
            $this->component('gpu', ['tdp' => 245]),
            $this->component('ram', ['modules' => 2]),
            $this->component('storage'),
        ];

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration(...$parts(), ...[$this->component('psu', ['wattage' => 550])]));

        $warning = $this->assertRuleStatus(CompatibilityStatus::Warning, $rule,
            $this->configuration(...$parts(), ...[$this->component('psu', ['wattage' => 450])]));
        $this->assertSame(['estimated_power' => 392, 'recommended_psu' => 550, 'selected_psu' => 450], $warning->details);
        $this->assertSame(['psu'], $warning->categories);

        $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule,
            $this->configuration(...$parts(), ...[$this->component('psu', ['wattage' => 350])]));
    }

    public function test_psu_wattage_applies_without_gpu_and_is_skipped_without_cpu(): void
    {
        $rule = new PsuWattageRule(new PowerCalculator(self::hardwareConfig()['power']));

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration($this->component('cpu', ['tdp' => 65]), $this->component('psu', ['wattage' => 450])));
        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($this->component('psu')));
    }

    public function test_display_output(): void
    {
        $rule = new DisplayOutputRule;

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration($this->component('cpu', ['has_integrated_graphics' => true])));
        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule, $this->configuration(
            $this->component('cpu', ['has_integrated_graphics' => false]), $this->component('gpu')));

        $result = $this->assertRuleStatus(CompatibilityStatus::Incompatible, $rule,
            $this->configuration($this->component('cpu', ['has_integrated_graphics' => false])));
        $this->assertSame(['gpu'], $result->categories);

        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($this->component('gpu')));
    }

    public function test_cpu_cooling(): void
    {
        $rule = new CpuCoolingRule;

        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule,
            $this->configuration($this->component('cpu', ['includes_cooler' => true])));
        $this->assertRuleStatus(CompatibilityStatus::Compatible, $rule, $this->configuration(
            $this->component('cpu', ['includes_cooler' => false]), $this->component('cooler')));
        $this->assertRuleStatus(CompatibilityStatus::Warning, $rule,
            $this->configuration($this->component('cpu', ['includes_cooler' => false])));
        $this->assertRuleStatus(CompatibilityStatus::Skipped, $rule, $this->configuration($this->component('cooler')));
    }
}
