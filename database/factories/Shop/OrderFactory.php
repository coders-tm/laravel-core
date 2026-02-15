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
        $location = Location::inRandomOrder()->first();

        return [
            'location_id' => $location?->id,
            'collect_tax' => rand(0, 1),
            'paid_total' => 0.00,
            'refund_total' => 0.00,
            'line_items_quantity' => 0,
            'created_at' => fake()->dateTimeBetween('-3 years'),
        ];
    }
}
