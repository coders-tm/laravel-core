<?php

namespace Coderstm\Database\Factories\Shop\Product;

use Coderstm\Models\Shop\Product\Weight;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeightFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Weight::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'unit' => ['kg', 'g', 'oz'][rand(0, 2)],
            'value' => rand(2, 5) / 2,
        ];
    }
}
