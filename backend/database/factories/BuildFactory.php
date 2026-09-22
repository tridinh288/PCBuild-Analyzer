<?php

namespace Database\Factories;

use App\Enums\BuildPurpose;
use App\Models\Build;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Build>
 */
class BuildFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => null,
            'purpose' => fake()->randomElement(BuildPurpose::cases()),
            'image_public_id' => null,
            'is_featured' => false,
        ];
    }
}
