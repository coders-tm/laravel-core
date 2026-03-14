<?php

namespace Coderstm\Database\Factories;

use Coderstm\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model|TModel>
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'subject' => fake()->sentence(),
            'message' => fake()->paragraph(),
        ];
    }
}
