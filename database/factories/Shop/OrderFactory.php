<?php

namespace Coderstm\Database\Factories\Shop;

use Coderstm\Models\Shop\Location;
use Coderstm\Models\Shop\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'location_id' => Location::inRandomOrder()->first()->id,
            'collect_tax' => rand(0, 1),
        ];
    }
}
