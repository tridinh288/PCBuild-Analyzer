<?php

namespace Tests\Feature\Console;

use App\Models\Build;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportImagesCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = base_path('storage/framework/testing/seed-images-'.uniqid());
        File::ensureDirectoryExists($this->root.'/products');
        File::ensureDirectoryExists($this->root.'/builds');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    /** Relative to base_path(), which is what the command's argument expects. */
    private function path(): string
    {
        return 'storage/framework/testing/'.basename($this->root);
    }

    private function image(string $folder, string $slug): void
    {
        // A one-pixel JPEG: the command only moves bytes, so the content does not matter.
        File::put($this->root."/{$folder}/{$slug}.jpg", base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAP//////////////////////////////////'.
            '////////////////////////////////////////////////////2wBDAf//////////'.
            '////////////////////////////////////////////////////////////////////'.
            '/////////8AAEQgAAQABAwEiAAIRAQMRAf/EABUAAQEAAAAAAAAAAAAAAAAAAAAI/8QA'.
            'FBABAAAAAAAAAAAAAAAAAAAAAP/EABUBAQEAAAAAAAAAAAAAAAAAAAAF/8QAFBEBAAAA'.
            'AAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AVAA//9k='
        ));
    }

    /** One shared category: its slug is unique, so a second one per product would collide. */
    private function product(string $slug): Product
    {
        $category = Category::query()->firstWhere('slug', 'cpu')
            ?? Category::factory()->state(['slug' => 'cpu'])->create();

        return Product::factory()->for($category)->create(['slug' => $slug]);
    }

    public function test_it_uploads_images_and_records_the_public_id(): void
    {
        $product = $this->product('amd-ryzen-7-7700');
        $build = Build::factory()->create(['slug' => 'gaming-1440p']);
        $this->image('products', 'amd-ryzen-7-7700');
        $this->image('builds', 'gaming-1440p');

        $this->artisan('app:import-images', ['path' => $this->path()])->assertSuccessful();

        $this->assertNotNull($product->refresh()->image_public_id);
        $this->assertNotNull($build->refresh()->image_public_id);
        $this->assertCount(2, $this->images->uploaded);
    }

    public function test_it_uploads_each_kind_into_its_configured_folder(): void
    {
        $this->product('amd-ryzen-7-7700');
        Build::factory()->create(['slug' => 'gaming-1440p']);
        $this->image('products', 'amd-ryzen-7-7700');
        $this->image('builds', 'gaming-1440p');

        $this->artisan('app:import-images', ['path' => $this->path()])->assertSuccessful();

        $folders = array_column($this->images->uploaded, 'folder');
        $this->assertContains(config('images.folders.products'), $folders);
        $this->assertContains(config('images.folders.builds'), $folders);
    }

    public function test_a_row_that_already_has_an_image_is_left_alone(): void
    {
        $product = $this->product('amd-ryzen-7-7700');
        $product->update(['image_public_id' => 'pcbuild/products/existing']);
        $this->image('products', 'amd-ryzen-7-7700');

        $this->artisan('app:import-images', ['path' => $this->path()])->assertSuccessful();

        $this->assertSame('pcbuild/products/existing', $product->refresh()->image_public_id);
        $this->assertSame([], $this->images->uploaded);
    }

    public function test_force_replaces_an_existing_image_and_deletes_the_old_one(): void
    {
        $product = $this->product('amd-ryzen-7-7700');
        $product->update(['image_public_id' => 'pcbuild/products/existing']);
        $this->image('products', 'amd-ryzen-7-7700');

        $this->artisan('app:import-images', ['path' => $this->path(), '--force' => true])->assertSuccessful();

        $this->assertNotSame('pcbuild/products/existing', $product->refresh()->image_public_id);
        $this->assertContains('pcbuild/products/existing', $this->images->deleted);
    }

    public function test_a_file_matching_no_row_fails_the_command(): void
    {
        $this->image('products', 'khong-co-trong-database');

        $this->artisan('app:import-images', ['path' => $this->path()])->assertFailed();

        $this->assertSame([], $this->images->uploaded);
    }

    public function test_dry_run_uploads_nothing(): void
    {
        $product = $this->product('amd-ryzen-7-7700');
        $this->image('products', 'amd-ryzen-7-7700');

        $this->artisan('app:import-images', ['path' => $this->path(), '--dry-run' => true])->assertSuccessful();

        $this->assertNull($product->refresh()->image_public_id);
        $this->assertSame([], $this->images->uploaded);
    }

    public function test_an_upload_failure_is_reported_and_fails_the_command(): void
    {
        $product = $this->product('amd-ryzen-7-7700');
        $this->image('products', 'amd-ryzen-7-7700');
        $this->images->failUploads = true;

        $this->artisan('app:import-images', ['path' => $this->path()])->assertFailed();

        $this->assertNull($product->refresh()->image_public_id);
    }

    /**
     * A broken image service fails every file identically, which buries the reason under one
     * line per file. Stopping at the first one keeps the message readable; the command is
     * idempotent, so a re-run carries on.
     */
    public function test_an_upload_failure_stops_the_run_instead_of_retrying_every_file(): void
    {
        $this->product('amd-ryzen-5-5600');
        $this->product('amd-ryzen-7-7700');
        Build::factory()->create(['slug' => 'gaming-1440p']);
        $this->image('products', 'amd-ryzen-5-5600');
        $this->image('products', 'amd-ryzen-7-7700');
        $this->image('builds', 'gaming-1440p');
        $this->images->failUploads = true;

        $this->artisan('app:import-images', ['path' => $this->path()])
            ->expectsOutputToContain('Uploaded 0, skipped 0, failed 1.')
            ->assertFailed();
    }

    /** Partial progress survives: the rows done before the failure keep their image. */
    public function test_rows_uploaded_before_a_failure_keep_their_image(): void
    {
        $first = $this->product('amd-ryzen-5-5600');
        $second = $this->product('amd-ryzen-7-7700');
        $this->image('products', 'amd-ryzen-5-5600');
        $this->image('products', 'amd-ryzen-7-7700');

        // Succeed once, then break the service, as a revoked credential or an outage would.
        $this->images->failAfter = 1;

        $this->artisan('app:import-images', ['path' => $this->path()])->assertFailed();

        $this->assertNotNull($first->refresh()->image_public_id);
        $this->assertNull($second->refresh()->image_public_id);
    }

    public function test_a_missing_folder_is_reported_instead_of_crashing(): void
    {
        $this->artisan('app:import-images', ['path' => 'storage/framework/testing/does-not-exist'])
            ->assertFailed();
    }
}
