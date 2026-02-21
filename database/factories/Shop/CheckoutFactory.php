<?php

namespace Coderstm\Database\Factories\Shop;

use Coderstm\Models\Shop\Checkout;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckoutFactory extends Factory
{
    protected $model = Checkout::class;

    public function definition()
    {
        return [
            'token' => $this->faker->uuid(),
            'email' => $this->faker->email(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone_number' => $this->faker->phoneNumber(),
            'sub_total' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withUser($user = null)
    {
        return $this->state([
            'user_id' => $user ? $user->id : \App\Models\User::factory()->create()->id,
        ]);
    }

    public function withEmail($email = null)
    {
        return $this->state([
            'email' => $email ?? $this->faker->email(),
        ]);
    }

    public function withTotals($subTotal = 1000, $taxTotal = 100, $discountTotal = 0)
    {
        $grandTotal = $subTotal + $taxTotal - $discountTotal;

        return $this->state([
            'sub_total' => $subTotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
        ]);
    }

    public function subscription()
    {
        return $this->state([
            'type' => 'subscription',
        ]);
    }

    public function standard()
    {
        return $this->state([
            'type' => 'standard',
        ]);
    }

    public function withCoupon($couponCode = null)
    {
        return $this->state([
            'coupon_code' => $couponCode ?? 'TEST10',
        ]);
    }
}
