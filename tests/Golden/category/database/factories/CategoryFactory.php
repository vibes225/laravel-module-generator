<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => null,
            'owner_id' => null,
            'name' => fake()->name(),
            'position' => fake()->numberBetween(0, 1000),
            'cover' => null,
        ];
    }
}
