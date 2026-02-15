<?php

namespace Coderstm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EnquiryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model|TModel>
     */
    protected $model = 'App\Models\Enquiry';

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
            'status' => ['Pending', 'Replied', 'Staff Replied', 'Resolved'][rand(0, 3)],
        ];
    }
}
