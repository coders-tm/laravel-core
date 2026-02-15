<?php

namespace Coderstm\Database\Factories\Shop\Product;

use Coderstm\Models\Shop\Product\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Collection::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => ucfirst(fake()->words(rand(2, 4), true)),
            'conditions_type' => ['manual', 'automated'][rand(0, 1)],
        ];
    }
}
