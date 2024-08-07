<?php

namespace Coderstm\Database\Factories\Shop\Product;

use Coderstm\Models\Shop\Product\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'available' => rand(-20, 20)
        ];
    }
}
