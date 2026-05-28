<?php

namespace Coderstm\Database\Factories\Shop\Product;

use Coderstm\Models\Shop\Product\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => ucfirst(fake()->words(rand(1, 2), true)),
            'description' => fake()->paragraph,
        ];
    }
}
