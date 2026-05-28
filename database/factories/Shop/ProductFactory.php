<?php

namespace Coderstm\Database\Factories\Shop;

use Coderstm\Models\Shop\Product;
use Coderstm\Models\Shop\Product\Category;
use Coderstm\Models\Shop\Product\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => fake()->sentence,
            'description' => fake()->paragraph,
            'has_variant' => rand(0, 1),
            'category' => Category::inRandomOrder()->first()?->toArray(),
            'vendor' => Vendor::inRandomOrder()->first()?->toArray(),
        ];
    }
}
