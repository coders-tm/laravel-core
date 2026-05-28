<?php

namespace Coderstm\Database\Factories\Shop;

use Coderstm\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Status::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'label' => ['Pending', 'Awaiting payment'][rand(0, 1)],
        ];
    }
}
