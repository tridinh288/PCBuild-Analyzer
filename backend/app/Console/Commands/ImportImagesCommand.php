<?php

namespace App\Console\Commands;

use App\Models\Build;
use App\Models\Product;
use App\Services\BuildService;
use App\Services\ProductService;
use App\Support\Images\ImageStorageException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Uploads the generated placeholder images (D-041) and records the returned public_id.
 *
 * Files are matched to rows by slug: `products/<product slug>.jpg`, `builds/<build slug>.jpg`.
 * Uploading goes through ProductService / BuildService rather than ImageStorage directly, so a
 * re-run deletes the image it replaces exactly as the admin screen does.
 *
 * Idempotent: rows that already carry an image are skipped unless --force is given, so an
 * interrupted run can simply be repeated without uploading the same file twice.
 *
 * Regenerate the files with `node tools/generate-seed-images.mjs` (needs Chrome, so run it
 * locally, not on the server).
 */
class ImportImagesCommand extends Command
{
    protected $signature = 'app:import-images
        {path=resources/seed-images : Folder holding products/ and builds/ subfolders}
        {--force : Replace images on rows that already have one}
        {--dry-run : List what would happen without uploading}';

    protected $description = 'Upload the generated placeholder images and set image_public_id';

    private int $uploaded = 0;

    private int $skipped = 0;

    private int $failed = 0;

    /** Set when the image service itself fails, so the remaining files are not retried. */
    private bool $aborted = false;

    public function handle(ProductService $products, BuildService $builds): int
    {
        $root = base_path((string) $this->argument('path'));

        if (! is_dir($root)) {
            $this->error("Folder not found: {$root}");

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('Dry run — nothing is uploaded.');
        }

        $this->section('Products', $root.'/products', Product::class,
            fn (Product $p, UploadedFile $f) => $products->replaceImage($p, $f));

        $this->section('Builds', $root.'/builds', Build::class,
            fn (Build $b, UploadedFile $f) => $builds->replaceImage($b, $f));

        $this->newLine();
        $this->line("Uploaded {$this->uploaded}, skipped {$this->skipped}, failed {$this->failed}.");

        if ($this->aborted) {
            $this->newLine();
            $this->error('Stopped at the first upload failure: the remaining files would fail the same way.');
            $this->line('Rows already uploaded keep their image. Fix the cause and run the command again '
                .'to carry on from where it stopped.');
        }

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $model
     * @param  callable(Model, UploadedFile): Model  $replace
     */
    private function section(string $label, string $folder, string $model, callable $replace): void
    {
        if ($this->aborted) {
            return;
        }

        $this->newLine();
        $this->line($label);

        if (! is_dir($folder)) {
            $this->warn("  no {$folder} folder, skipped");

            return;
        }

        $files = iterator_to_array(
            Finder::create()->files()->in($folder)->name('/\.(jpe?g|png|webp)$/i')->sortByName()
        );

        if ($files === []) {
            $this->warn('  no image files, skipped');

            return;
        }

        foreach ($files as $file) {
            if ($this->aborted) {
                return;
            }

            $this->importOne($file, $model, $replace);
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  callable(Model, UploadedFile): Model  $replace
     */
    private function importOne(SplFileInfo $file, string $model, callable $replace): void
    {
        $slug = $file->getBasename('.'.$file->getExtension());
        $row = $model::query()->where('slug', $slug)->first();

        if (! $row) {
            $this->failed++;
            $this->error("  {$slug} — no row with this slug");

            return;
        }

        if ($row->image_public_id && ! $this->option('force')) {
            $this->skipped++;
            $this->line("  {$slug} — already has an image, skipped");

            return;
        }

        if ($this->option('dry-run')) {
            $this->uploaded++;
            $this->line("  {$slug} — would upload ".$this->humanSize($file->getSize()));

            return;
        }

        try {
            $replace($row, $this->asUpload($file));
            $this->uploaded++;
            $this->info("  {$slug} — uploaded");
        } catch (ImageStorageException $e) {
            // Every cause of this is systemic — unconfigured, unreachable, credentials rejected —
            // so carrying on only prints the same line once per file and buries the reason.
            $this->failed++;
            $this->aborted = true;
            $this->error("  {$slug} — {$e->getMessage()}");
        }
    }

    /**
     * The services expect what the admin controller hands them. `test: true` only tells Symfony
     * the file did not arrive through a real upload, so it skips the is_uploaded_file() check.
     */
    private function asUpload(SplFileInfo $file): UploadedFile
    {
        return new UploadedFile(
            $file->getPathname(),
            $file->getFilename(),
            mime_content_type($file->getPathname()) ?: null,
            null,
            true,
        );
    }

    private function humanSize(int $bytes): string
    {
        return round($bytes / 1024).' KB';
    }
}
