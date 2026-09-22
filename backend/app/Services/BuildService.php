<?php

namespace App\Services;

use App\Domain\Analysis\BuildAnalyzer;
use App\Domain\Analysis\Results\BuildAnalysis;
use App\Models\Build;
use App\Support\Images\ImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Admin writes for template builds: details, components, image.
 */
class BuildService
{
    public function __construct(
        private readonly ImageStorage $images,
        private readonly BuildConfigurationFactory $configurations,
        private readonly BuildAnalyzer $analyzer,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated BuildRequest data
     */
    public function create(array $data): Build
    {
        $build = new Build(collect($data)->except('slug')->all());
        $build->slug = $this->uniqueSlug($data['slug'] ?? $data['name']);
        $build->save();

        return $build;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Build $build, array $data): Build
    {
        $build->fill(collect($data)->except('slug')->all());

        if (filled($data['slug'] ?? null)) {
            $build->slug = $data['slug'];
        }

        $build->save();

        return $build;
    }

    /**
     * Items cascade in the database; the image lives outside it.
     */
    public function delete(Build $build): void
    {
        $build->delete();

        if ($build->image_public_id) {
            $this->images->delete($build->image_public_id);
        }
    }

    /**
     * Replaces the whole component list atomically.
     *
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function syncItems(Build $build, array $items): Build
    {
        DB::transaction(function () use ($build, $items) {
            $build->items()->delete();
            $build->items()->createMany($items);
        });

        return $build->unsetRelation('items');
    }

    /**
     * Engine feedback for the admin while editing (same analysis as the public pages).
     */
    public function analyze(Build $build): BuildAnalysis
    {
        return $this->analyzer->analyze($this->configurations->fromBuild($build), $build->purpose);
    }

    public function replaceImage(Build $build, UploadedFile $file): Build
    {
        $previous = $build->image_public_id;

        $build->image_public_id = $this->images->upload($file, config('images.folders.builds'));
        $build->save();

        if ($previous) {
            $this->images->delete($previous);
        }

        return $build;
    }

    public function removeImage(Build $build): Build
    {
        if ($build->image_public_id) {
            $this->images->delete($build->image_public_id);
            $build->image_public_id = null;
            $build->save();
        }

        return $build;
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source);
        $slug = $base;

        for ($i = 2; Build::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
