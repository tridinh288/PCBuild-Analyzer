<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Fixed length: the slug column is VARCHAR(32), and fake()->slug() sometimes exceeded it,
        // which made tests fail at random.
        $slug = 'test-'.fake()->unique()->lexify('??????????');

        return [
            'slug' => $slug,
            'name' => ucfirst($slug),
            'description' => null,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
