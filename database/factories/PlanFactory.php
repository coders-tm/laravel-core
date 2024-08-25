<?php

namespace Coderstm\Database\Factories;

use Coderstm\Models\Subscription\Feature;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition()
    {
        $interval = $this->faker->randomElement(['day', 'week', 'month', 'year']);

        return [
            'label' => $this->faker->word,
            'description' => $this->faker->sentence,
            'is_active' => $this->faker->boolean,
            'default_interval' => $interval,
            'interval' => $interval,
            'interval_count' => $this->faker->numberBetween(1, 12),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'trial_days' => $this->faker->numberBetween(0, 30),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($plan) {
            $features = [];

            foreach (Feature::all() as $key => $item) {
                $features[$item->slug] = $item->isBoolean() ? rand(0, 1) : rand(0, 20);
            }

            $plan->syncFeatures($features);
        });
    }
}
