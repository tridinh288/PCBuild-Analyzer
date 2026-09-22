<?php

namespace App\Services;

use App\Domain\Analysis\BuildAnalyzer;
use App\Domain\Analysis\Results\BuildAnalysis;
use App\Domain\Compatibility\CompatibilityEngine;
use App\Domain\Configuration\BuildConfiguration;
use App\Enums\BuildPurpose;
use App\Enums\CompatibilityStatus;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;

/**
 * Builder use cases: candidate options for a slot, and analysis of a custom selection.
 * Stateless: nothing is written to the database (D-019).
 */
class BuilderService
{
    public function __construct(
        private readonly BuildConfigurationFactory $configurations,
        private readonly ProductRepositoryInterface $products,
        private readonly CompatibilityEngine $engine,
        private readonly BuildAnalyzer $analyzer,
    ) {}

    /**
     * Every active product of the category that matches the filters (SQL), each evaluated
     * against the current selection (PHP rules). Incompatible parts stay selectable (D-018);
     * $compatibleOnly hides them and keeps warnings (D-030).
     *
     * @param  array<string, mixed>  $selected
     * @param  array<string, mixed>  $filters
     * @return array{options: list<BuilderOption>, missing: list<array{category: string, id: int}>}
     */
    public function optionsFor(
        string $category,
        array $selected,
        array $filters = [],
        bool $compatibleOnly = false,
        ?string $sort = null,
    ): array {
        $resolved = $this->configurations->fromSelection($selected);
        $current = $resolved->configuration;
        $selectedIds = array_map(fn ($item) => $item->component->id(), $current->items($category));

        // A RAM candidate keeps the number of kits already chosen.
        $quantity = $category === 'ram' ? max(1, $current->quantityOf('ram')) : 1;

        $options = [];

        foreach ($this->products->search($category, $filters, $sort) as $product) {
            $option = $this->evaluate($current, $product, $category, $quantity, in_array($product->id, $selectedIds, true));

            if (! $compatibleOnly || $option->status !== CompatibilityStatus::Incompatible) {
                $options[] = $option;
            }
        }

        return ['options' => $options, 'missing' => $resolved->missing];
    }

    /**
     * Also returns the selected products, so a Builder opened from a link (IDs only) can show
     * names, prices and specs without extra requests.
     *
     * @param  array<string, mixed>  $selected
     * @return array{analysis: BuildAnalysis, items: list<array{category: string, quantity: int, product: Product}>,
     *     missing: list<array{category: string, id: int}>}
     */
    public function analyze(array $selected, ?BuildPurpose $profile = null): array
    {
        $resolved = $this->configurations->fromSelection($selected);
        $configuration = $resolved->configuration;

        $ids = array_map(fn ($item) => $item->component->id(), $configuration->allItems());
        $products = $this->products->findActiveByIds($ids)->keyBy('id');

        return [
            'analysis' => $this->analyzer->analyze($configuration, $profile),
            'items' => array_map(fn ($item) => [
                'category' => $item->category(),
                'quantity' => $item->quantity,
                'product' => $products[$item->component->id()],
            ], $configuration->allItems()),
            'missing' => $resolved->missing,
        ];
    }

    private function evaluate(
        BuildConfiguration $current,
        Product $product,
        string $category,
        int $quantity,
        bool $selected,
    ): BuilderOption {
        $component = $this->configurations->component($product);

        // For storage, a candidate that is already selected is evaluated as it is now, not doubled.
        $candidate = $selected && $current->slotRules()->acceptsSeveralProducts($category)
            ? $current
            : $current->with($component, $quantity);

        $report = $this->engine->checkCandidate($candidate, $category);

        return new BuilderOption($product, $report->status(), $report->problems(), $selected);
    }
}
