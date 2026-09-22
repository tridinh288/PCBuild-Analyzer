<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Support\Hardware\SpecSchema;
use Illuminate\Database\Seeder;

/**
 * Creates the fixed set of categories from config/hardware.php (D-006).
 * Existing rows keep the name and order the admin may have edited.
 */
class CategorySeeder extends Seeder
{
    public function run(SpecSchema $schema): void
    {
        foreach ($schema->categories() as $slug) {
            Category::firstOrCreate(['slug' => $slug], $schema->categoryDefaults($slug));
        }
    }
}
