<?php

namespace App\Domain\Analysis;

use App\Domain\Analysis\Results\BuildAnalysis;
use App\Domain\Compatibility\CompatibilityEngine;
use App\Domain\Configuration\BuildConfiguration;
use App\Enums\BuildPurpose;

/**
 * The PC Build Analysis Engine entry point (spec section 11): the same analysis for
 * templates and custom configurations. Every part is injected (DI).
 */
final class BuildAnalyzer
{
    public const INCOMPATIBLE_NOTE = 'Cấu hình hiện có linh kiện không tương thích nên chưa thể lắp ráp; điểm chỉ mang tính tham khảo.';

    public const INCOMPLETE_NOTE = 'Cấu hình chưa hoàn chỉnh; phần còn thiếu được tính 0 điểm.';

    public function __construct(
        private readonly CompatibilityEngine $compatibilityEngine,
        private readonly PowerCalculator $powerCalculator,
        private readonly PriceAnalyzer $priceAnalyzer,
        private readonly PerformanceAnalyzer $performanceAnalyzer,
    ) {}

    public function analyze(BuildConfiguration $config, ?BuildPurpose $profile = null): BuildAnalysis
    {
        $compatibility = $this->compatibilityEngine->check($config);
        $missingSlots = $config->missingRequiredSlots();
        $performance = $this->performanceAnalyzer->analyze($config, $profile);

        // Power, price and score still run on invalid or incomplete configurations (D-018).
        if ($compatibility->hasErrors()) {
            $performance = $performance->withNote(self::INCOMPATIBLE_NOTE);
        }

        if ($missingSlots !== []) {
            $performance = $performance->withNote(self::INCOMPLETE_NOTE);
        }

        return new BuildAnalysis(
            compatibility: $compatibility,
            power: $this->powerCalculator->calculate($config),
            price: $this->priceAnalyzer->analyze($config),
            performance: $performance,
            missingSlots: $missingSlots,
        );
    }
}
