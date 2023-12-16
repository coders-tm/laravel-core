<?php

namespace Coderstm\Database\Seeders;

use Coderstm\Models\Plan;
use Coderstm\Enum\AppStatus;
use Illuminate\Database\Seeder;
use Coderstm\Models\Cashier\Subscription;
use Coderstm\Database\Factories\UserFactory;
use Coderstm\Database\Factories\AddressFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserFactory::new()
            ->count(10)
            ->create()
            ->each(function ($user) {
                $user->updateOrCreateAddress(AddressFactory::new()->make()->toArray());
                if ($user->status == AppStatus::ACTIVE) {
                    Subscription::withoutEvents(function () use ($user) {
                        if ($plan = Plan::inRandomOrder()->first()) {
                            if ($plan->is_custom) {
                                $price = $plan->prices[0];
                            } else {
                                $price = $plan->prices[rand(0, 1)]; // 0 => month and 1 => year
                            }
                        }

                        // Generate a fake subscription
                        $subscription = Subscription::create([
                            'user_id' => $user->id,
                            'name' => 'default',
                            'stripe_id' => fake()->unique()->lexify('sub_????????????????'),
                            'stripe_status' => 'active',
                            'stripe_price' => $price->stripe_id,
                            'quantity' => 1,
                            'trial_ends_at' => now()->addDays(14),
                            'created_at' => $user->created_at,
                            'ends_at' => rand(0, 1) ? $user->created_at->addMonths(rand(5, 8)) : null,
                        ]);

                        // Associate the subscription with the user
                        $user->subscriptions()->save($subscription);
                    });
                }
            });
    }
}
