<?php

namespace App\Domain\Analysis;

use App\Domain\Analysis\Results\BuildAnalysis;
use App\Domain\Analysis\Results\ComparisonResult;
use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Configuration\ConfigurationItem;

/**
 * Aligns analyzed configurations by slot and lists "Có gì khác nhau?" between each
 * configuration and the next one. Facts only, no winner (D-017).
 */
final class ComparisonAnalyzer
{
    /**
     * @param  list<array{label: string, configuration: BuildConfiguration, analysis: BuildAnalysis}>  $entries
     */
    public function compare(array $entries): ComparisonResult
    {
        $slots = [];

        foreach ($entries[0]['configuration']->slotRules()->categories() as $category) {
            $slots[] = [
                'category' => $category,
                'values' => array_map(fn (array $entry) => $this->names($entry['configuration'], $category), $entries),
            ];
        }

        $differences = [];

        for ($i = 0; $i < count($entries) - 1; $i++) {
            $differences[] = [
                'from' => $i,
                'to' => $i + 1,
                'changes' => $this->changes($slots, $i, $entries[$i]['analysis'], $entries[$i + 1]['analysis']),
            ];
        }

        return new ComparisonResult(
            configurations: array_map(fn (array $entry) => $this->metrics($entry['label'], $entry['analysis']), $entries),
            slots: $slots,
            differences: $differences,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function metrics(string $label, BuildAnalysis $analysis): array
    {
        return [
            'label' => $label,
            'total_price' => $analysis->price->total,
            'estimated_watts' => $analysis->power->estimatedWatts,
            'recommended_psu_watts' => $analysis->power->recommendedPsuWatts,
            'compatibility_status' => $analysis->compatibility->status()->value,
            'errors' => $analysis->compatibility->errors(),
            'warnings' => $analysis->compatibility->warnings(),
            'score' => $analysis->performance->score,
            'profile' => $analysis->performance->profile->value,
            'missing_slots' => $analysis->missingSlots,
        ];
    }

    /**
     * @return list<string> e.g. ['Kingston FURY 16GB × 2']
     */
    private function names(BuildConfiguration $config, string $category): array
    {
        return array_map(
            fn (ConfigurationItem $item) => $item->component->name().($item->quantity > 1 ? " × {$item->quantity}" : ''),
            $config->items($category),
        );
    }

    /**
     * @param  list<array{category: string, values: list<list<string>>}>  $slots
     * @return list<array<string, mixed>>
     */
    private function changes(array $slots, int $index, BuildAnalysis $from, BuildAnalysis $to): array
    {
        $changes = [];

        foreach ($slots as $slot) {
            [$before, $after] = [$slot['values'][$index], $slot['values'][$index + 1]];

            if ($before !== $after) {
                $changes[] = ['type' => 'slot', 'category' => $slot['category'], 'from' => $before, 'to' => $after];
            }
        }

        $metrics = [
            'estimated_watts' => [$from->power->estimatedWatts, $to->power->estimatedWatts],
            'total_price' => [$from->price->total, $to->price->total],
            'score' => [$from->performance->score, $to->performance->score],
        ];

        foreach ($metrics as $metric => [$before, $after]) {
            if ($before !== $after) {
                $changes[] = ['type' => 'metric', 'metric' => $metric, 'from' => $before, 'to' => $after, 'delta' => $after - $before];
            }
        }

        return $changes;
    }
}
