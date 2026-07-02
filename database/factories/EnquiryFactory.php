<?php

namespace Coderstm\Database\Factories;

use Coderstm\Coderstm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class EnquiryFactory extends Factory
{
    /**
     * Get the name of the model that the factory corresponds to.
     */
    public function modelName(): string
    {
        return Coderstm::$enquiryModel;
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'subject' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'status' => ['pending', 'replied', 'staff_replied', 'resolved'][rand(0, 3)],
        ];
    }
}
