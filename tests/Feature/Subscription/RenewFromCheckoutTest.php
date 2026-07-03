<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Carbon\Carbon;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class RenewFromCheckoutTest extends FeatureTestCase
{
    #[Test]
    public function it_extends_expires_at_for_active_subscription_without_resetting_credits()
    {
        $plan = Plan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $user = User::factory()->create();
        $originalExpiresAt = Carbon::parse('2025-03-01');
        $newExpiresAt = $originalExpiresAt->copy()->addMonth();

        // Create subscription first (this triggers syncFeaturesFromPlan from seeded features)
        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::parse('2025-02-01'),
            'expires_at' => $originalExpiresAt,
        ]);
        $subscription->save();

        // Manually create our test feature on this subscription
        $feature = $subscription->features()->create([
            'slug' => 'api-calls',
            'label' => 'API Calls',
            'type' => 'integer',
            'resetable' => true,
            'value' => 1000,
            'used' => 0,
        ]);
        $this->assertNotNull($feature, 'Feature should be created on subscription');

        $subscription->recordFeatureUsage('api-calls', 500);
        $this->assertEquals(500, $subscription->getFeatureUsage('api-calls'), 'getFeatureUsage should return 500 before renew');

        $subscription->renew(false);

        $subscription->refresh();
        $this->assertEquals($newExpiresAt->format('Y-m-d'), $subscription->expires_at->format('Y-m-d'));
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals(500, $subscription->getFeatureUsage('api-calls'), 'Credits should NOT be reset for active subscription');
    }

    #[Test]
    public function it_resets_credits_and_advances_credit_resets_at_when_expired()
    {
        $plan = Plan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $user = User::factory()->create();
        $originalExpiresAt = Carbon::parse('2025-01-01');
        $newExpiresAt = $originalExpiresAt->copy()->addMonth();

        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::EXPIRED,
            'starts_at' => Carbon::parse('2024-12-01'),
            'expires_at' => $originalExpiresAt,
            'credit_resets_at' => Carbon::parse('2025-04-01'),
        ]);
        $subscription->save();

        // Manually create our test feature on this subscription
        $feature = $subscription->features()->create([
            'slug' => 'api-calls',
            'label' => 'API Calls',
            'type' => 'integer',
            'resetable' => true,
            'value' => 1000,
            'used' => 0,
        ]);
        $this->assertNotNull($feature, 'Feature should be created on subscription');

        $subscription->recordFeatureUsage('api-calls', 500);

        $subscription->renew(false);

        $subscription->refresh();
        $this->assertEquals($newExpiresAt->format('Y-m-d'), $subscription->expires_at->format('Y-m-d'));
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals(0, $subscription->getFeatureUsage('api-calls'), 'Credits should be reset for expired subscription');
        $this->assertNotNull($subscription->credit_resets_at, 'credit_resets_at should be advanced');
        $this->assertEquals(
            Carbon::parse('2025-05-01')->format('Y-m-d'),
            $subscription->credit_resets_at->format('Y-m-d'),
            'credit_resets_at should be advanced by plan interval'
        );
    }

    #[Test]
    public function it_renew_early_requires_authenticated_user()
    {
        $subscription = Subscription::factory()->create();

        $this->postJson(route('subscriptions.renew', $subscription->id))
            ->assertStatus(401);
    }

    #[Test]
    public function it_rejects_renewal_when_contract_cycles_exhausted()
    {
        $plan = Plan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'is_contract' => true,
            'contract_cycles' => 2,
        ]);

        $user = User::factory()->create();

        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => 'active',
            'total_cycles' => 2,
            'current_cycle' => 2,
            'starts_at' => Carbon::parse('2025-01-01'),
            'expires_at' => Carbon::parse('2025-03-01'),
        ]);
        $subscription->save();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Contract has reached its total cycles limit.');

        $subscription->renew(false);
    }

    #[Test]
    public function it_clears_ends_at_and_trial_ends_at_on_renew()
    {
        $plan = Plan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $user = User::factory()->create();
        $originalExpiresAt = Carbon::parse('2025-03-01');

        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::parse('2025-02-01'),
            'expires_at' => $originalExpiresAt,
            'ends_at' => Carbon::parse('2025-03-15'),
            'trial_ends_at' => Carbon::parse('2025-02-15'),
        ]);
        $subscription->save();

        $subscription->renew(false);

        $subscription->refresh();
        $this->assertNull($subscription->ends_at);
        $this->assertNull($subscription->trial_ends_at);
    }
}
