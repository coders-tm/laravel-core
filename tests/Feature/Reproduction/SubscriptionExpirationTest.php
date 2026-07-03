<?php

namespace Tests\Feature\Reproduction;

use App\Models\User;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Support\Facades\Config;
use Tests\Feature\FeatureTestCase;

class SubscriptionExpirationTest extends FeatureTestCase
{
    public function test_it_expires_subscription_when_no_grace_period_and_no_wallet_balance_on_renew()
    {
        // 1. Disable wallet auto charge
        Config::set('coderstm.wallet.auto_charge_on_renewal', false);
        Config::set('coderstm.subscription.grace_period_days', 0);

        // 2. Create a paid plan with 0 grace period
        $plan = Plan::factory()->create([
            'price' => 50.00,
            'grace_period_days' => 0,
        ]);

        // 3. Create a user and subscription
        $user = User::factory()->create();
        $subscription = Subscription::withoutEvents(function () use ($user, $plan) {
            return Subscription::factory()->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::ACTIVE,
                'expires_at' => now()->subDay(),
            ]);
        });

        // 4. Run the renewal
        $subscription->renew();

        // 5. Assert: It should be expired because it wasn't paid and has no grace period
        $this->assertEquals(SubscriptionStatus::EXPIRED, $subscription->fresh()->status, 'Subscription should be EXPIRED');
    }

    public function test_it_expires_trialing_subscription_after_trial_expires_with_no_balance()
    {
        // 1. Enable wallet auto charge (default)
        Config::set('coderstm.wallet.auto_charge_on_renewal', true);
        Config::set('coderstm.subscription.grace_period_days', 0);

        // 2. Create a paid plan with 0 grace period
        $plan = Plan::factory()->create([
            'price' => 50.00,
            'grace_period_days' => 0,
        ]);

        // 3. Create a user (0 balance) and trialing subscription
        $user = User::factory()->create();
        $subscription = Subscription::withoutEvents(function () use ($user, $plan) {
            return Subscription::factory()->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::TRIALING,
                'expires_at' => now()->subDay(),
            ]);
        });

        // 4. Run the renewal command (as it sets status to ACTIVE initially)
        $this->artisan('coderstm:subscriptions-renew');

        // 5. Assert: It should be expired because it wasn't paid and has no grace period
        $this->assertEquals(SubscriptionStatus::EXPIRED, $subscription->fresh()->status, 'Trialing subscription should be EXPIRED after trial ends without balance');
    }
}
