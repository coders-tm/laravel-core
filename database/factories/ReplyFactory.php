<?php

namespace Coderstm\Database\Factories;

use Coderstm\Coderstm;
use Coderstm\Models\Enquiry\Reply;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReplyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model|TModel>
     */
    protected $model = Reply::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'message' => fake()->paragraph(),
            'user_id' => Coderstm::$adminModel::inRandomOrder()->first()->id,
        ];
    }
}
