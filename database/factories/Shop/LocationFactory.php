<?php

namespace Coderstm\Database\Factories\Shop;

use Coderstm\Models\Shop\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fake()->city,
            'line1' => fake()->streetAddress,
            'city' => fake()->city,
            'country' => fake()->country,
            'country_code' => fake()->countryCode,
            'state' => fake()->state,
            'postal_code' => fake()->postcode,
        ];
    }
}
