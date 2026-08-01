<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'restaurant_id' => fn (array $attributes) => Category::findOrFail($attributes['category_id'])->restaurant_id,
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 0, 500),
            'image' => null,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
