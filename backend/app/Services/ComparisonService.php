<?php

namespace App\Services;

use App\Domain\Analysis\BuildAnalyzer;
use App\Domain\Analysis\ComparisonAnalyzer;
use App\Domain\Analysis\Results\ComparisonResult;
use App\Enums\BuildPurpose;
use App\Models\Build;
use App\Repositories\Contracts\BuildRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Resolves each input (template slug or custom selection), analyzes all of them with the
 * same profile, and delegates the comparison to the Domain.
 */
class ComparisonService
{
    public function __construct(
        private readonly BuildRepositoryInterface $builds,
        private readonly BuildConfigurationFactory $configurations,
        private readonly BuildAnalyzer $analyzer,
        private readonly ComparisonAnalyzer $comparison,
    ) {}

    /**
     * @param  list<array{type: string, slug?: string, selected?: array<string, mixed>, label?: string}>  $inputs
     * @return array{comparison: ComparisonResult, missing: list<list<array{category: string, id: int}>>}
     *
     * @throws ModelNotFoundException when a template slug does not exist
     */
    public function compare(array $inputs, ?BuildPurpose $profile = null): array
    {
        $profile ??= BuildPurpose::GeneralUse;
        $entries = [];
        $missing = [];
        $customCount = 0;

        foreach ($inputs as $input) {
            if ($input['type'] === 'template') {
                $build = $this->builds->findBySlug($input['slug'])
                    ?? throw (new ModelNotFoundException)->setModel(Build::class, [$input['slug']]);
                $configuration = $this->configurations->fromBuild($build);
                $label = $build->name;
                $missing[] = [];
            } else {
                $resolved = $this->configurations->fromSelection($input['selected'] ?? []);
                $configuration = $resolved->configuration;
                $label = $input['label'] ?? 'Cấu hình tùy chỉnh '.(++$customCount);
                $missing[] = $resolved->missing;
            }

            $entries[] = [
                'label' => $label,
                'configuration' => $configuration,
                'analysis' => $this->analyzer->analyze($configuration, $profile),
            ];
        }

        return ['comparison' => $this->comparison->compare($entries), 'missing' => $missing];
    }
}
