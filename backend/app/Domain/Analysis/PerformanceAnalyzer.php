<?php

namespace App\Domain\Analysis;

use App\Domain\Analysis\Contracts\AnalysisStrategy;
use App\Domain\Analysis\Results\PerformanceResult;
use App\Domain\Configuration\BuildConfiguration;
use App\Enums\BuildPurpose;
use InvalidArgumentException;

/**
 * Picks the strategy for the requested profile (General Use by default) and runs it.
 */
final class PerformanceAnalyzer
{
    public const DISCLAIMER = 'Điểm cấu hình ước tính theo quy tắc của dự án, không phải kết quả benchmark.';

    /** @var array<string, AnalysisStrategy> */
    private readonly array $strategies;

    /**
     * @param  iterable<AnalysisStrategy>  $strategies
     */
    public function __construct(iterable $strategies)
    {
        $byProfile = [];

        foreach ($strategies as $strategy) {
            $byProfile[$strategy->profile()->value] = $strategy;
        }

        $this->strategies = $byProfile;
    }

    public function analyze(BuildConfiguration $config, ?BuildPurpose $profile = null): PerformanceResult
    {
        $profile ??= BuildPurpose::GeneralUse;

        $strategy = $this->strategies[$profile->value]
            ?? throw new InvalidArgumentException("No analysis strategy for profile [{$profile->value}].");

        return $strategy->score($config)->withNote(self::DISCLAIMER);
    }
}
