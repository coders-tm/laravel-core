<?php

namespace Coderstm\Database\Factories;

use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\SubscriptionFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFeatureFactory extends Factory
{
    protected $model = SubscriptionFeature::class;

    public function definition()
    {
        return [
            'subscription_id' => Subscription::factory(),
            'slug' => $this->faker->unique()->slug,
            'label' => $this->faker->word,
            'type' => $this->faker->randomElement(['integer', 'boolean']),
            'resetable' => $this->faker->boolean,
            'value' => $this->faker->numberBetween(0, 100),
            'used' => $this->faker->numberBetween(0, 50),
            'reset_at' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }
}
