<?php

namespace Workbench\Database\Factories;

use Illuminate\Support\Str;
use Workbench\App\Models\Coupon;
use Coderstm\Enum\CouponDuration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \Workbench\App\Coupon
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fixed = $this->faker->boolean;

        return [
            'name' => $this->faker->word,
            'promotion_code' => Str::upper(Str::random(10)),
            'duration' => ['forever', 'once', 'repeating'][rand(0, 2)],
            'max_redemptions' => $this->faker->numberBetween(1, 100),
            'percent_off' => !$fixed ? $this->faker->optional()->randomFloat(2, 1, 100) : 0,
            'amount_off' => $fixed ? $this->faker->optional()->randomNumber(2) : 0,
            'fixed' => $fixed,
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }
}
