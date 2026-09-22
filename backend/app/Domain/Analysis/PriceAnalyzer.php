<?php

namespace App\Domain\Analysis;

use App\Domain\Analysis\Results\PriceResult;
use App\Domain\Configuration\BuildConfiguration;

/**
 * Total cost (respecting quantity) and cost share per category, in integer VND (spec section 15).
 * The same total as Build::withTotalPrice() for templates (D-032).
 */
final class PriceAnalyzer
{
    public function analyze(BuildConfiguration $config): PriceResult
    {
        $amounts = [];

        foreach ($config->allItems() as $item) {
            $amounts[$item->category()] = ($amounts[$item->category()] ?? 0) + $item->totalPrice();
        }

        $total = array_sum($amounts);
        $byCategory = [];

        foreach ($amounts as $category => $amount) {
            $byCategory[] = [
                'category' => $category,
                'amount' => $amount,
                'percent' => $total > 0 ? round($amount / $total * 100, 1) : 0.0,
            ];
        }

        return new PriceResult($total, $byCategory);
    }
}
