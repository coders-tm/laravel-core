<?php

namespace Coderstm\Database\Factories\Shop;

use Coderstm\Coderstm;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * Get the name of the model that the factory corresponds to.
     */
    public function modelName(): string
    {
        return Coderstm::$orderModel;
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'collect_tax' => rand(0, 1),
            'paid_total' => 0.00,
            'refund_total' => 0.00,
            'line_items_quantity' => 0,
            'created_at' => fake()->dateTimeBetween('-3 years'),
        ];
    }
}
