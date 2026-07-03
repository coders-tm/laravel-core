<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Test the complete subscription lifecycle with states, grace periods,
 * and contract cycles.
 */
class SubscriptionLifecycleTest extends TestCase
{
    /**
     * Test subscription creation with PENDING status and invoice generation.
     */
    public function test_subscription_starts_in_pending_status()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        $this->assertEquals(SubscriptionStatus::PENDING, $subscription->status);
        $this->assertNotNull($subscription->latestInvoice);
        $this->assertFalse($subscription->latestInvoice->is_paid);
    }

    /**
     * Test transition from PENDING to ACTIVE on payment.
     */
    public function test_subscription_transitions_to_active_on_payment()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        $this->assertEquals(SubscriptionStatus::PENDING, $subscription->status);

        // Simulate payment confirmation
        $subscription->paymentConfirmation();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    /**
     * Test subscription stays ACTIVE when payment fails but enters grace period.
     */
    public function test_active_subscription_enters_grace_on_payment_failure()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        $subscription->paymentConfirmation();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);

        // Set expires_at to future (next billing period) and ends_at to near future (within grace period)
        // This simulates the renewal scenario where customer hasn't paid yet
        $subscription->expires_at = now()->addMonth(); // Next billing period
        $subscription->ends_at = now()->addDays(6); // Grace period end (before expires_at)
        $subscription->save();

        // Simulate payment failure
        $subscription->paymentFailed();

        // Status stays ACTIVE, but onGracePeriod() returns true
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->onGracePeriod());
        $this->assertFalse($subscription->notOnGracePeriod());
    }

    /**
     * Test subscription generates invoice with ACTIVE status when renewing without payment.
     */
    public function test_renewal_without_payment_sets_grace_status()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        $subscription->paymentConfirmation();

        // Generate invoice (renewal scenario)
        $invoice = $subscription->generateInvoice();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertNotNull($invoice);
        $this->assertFalse($invoice->is_paid);
    }

    /**
     * Test grace period status is recognized as valid.
     */
    public function test_grace_status_is_valid()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        $subscription->paymentConfirmation();

        // Set up subscription in grace period (after renewal, waiting for payment)
        $subscription->status = SubscriptionStatus::ACTIVE;
        $subscription->starts_at = now();
        $subscription->expires_at = now()->addMonth(); // Next billing period
        $subscription->ends_at = now()->addDays(7); // Grace period ends in 7 days (before expires_at)
        $subscription->save();

        $this->assertTrue($subscription->onGracePeriod());
        $this->assertTrue($subscription->valid());
    }

    /**
     * Test payment during grace period transitions back to fully ACTIVE.
     */
    public function test_payment_during_grace_reactivates_subscription()
    {
        $user = User::factory()->create();
        // Create plan with grace period enabled (7 days)
        $plan = Plan::factory()->withGracePeriod(7)->create(['price' => 1000, 'trial_days' => 0]);

        $subscription = $user->newSubscription('default', $plan->id)->saveAndInvoice([], true);
        $subscription->paymentConfirmation();

        // Simulate renewal that enters grace period (unpaid)
        $subscription->renew(); // This creates new period with grace

        $this->assertTrue($subscription->onGracePeriod());
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);

        // Simulate payment during grace period - should exit grace
        $subscription->paymentConfirmation();

        // After payment, subscription should no longer be in grace
        // Payment confirmation should clear ends_at (grace period)
        $subscription->refresh();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertNull($subscription->ends_at); // Grace period cleared by payment
        $this->assertFalse($subscription->onGracePeriod()); // No longer in grace (payment made)
        $this->assertTrue($subscription->expires_at->isFuture());
    }

    /**
     * Test billing interval is stored.
     */
    public function test_billing_interval_is_stored()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'interval' => 'month', 'trial_days' => 0]);

        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        $this->assertEquals('month', $subscription->billing_interval);
    }

    /**
     * Test grace period scope query.
     */
    public function test_grace_scope_filters_subscriptions()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        // Create subscriptions in different states
        $activeSubscription = $user->newSubscription('active', $plan->id);
        $activeSubscription->status = SubscriptionStatus::ACTIVE;
        $activeSubscription->expires_at = now()->addMonth(); // Not expired
        $activeSubscription->save();

        $graceSubscription = $user->newSubscription('grace', $plan->id);
        $graceSubscription->status = SubscriptionStatus::ACTIVE;
        $graceSubscription->expires_at = now()->addMonth(); // Next billing period
        $graceSubscription->ends_at = now()->addDays(4); // Grace period ends in 4 days (before expires_at)
        $graceSubscription->save();

        $expiredSubscription = $user->newSubscription('expired', $plan->id);
        $expiredSubscription->status = SubscriptionStatus::EXPIRED;
        $expiredSubscription->save();

        // Query only grace period subscriptions
        $graceSubscriptions = Subscription::query()->onGracePeriod()->get();

        $this->assertCount(1, $graceSubscriptions);
        $this->assertEquals($graceSubscription->id, $graceSubscriptions->first()->id);

        // Test notOnGracePeriod scope
        $notGraceSubscriptions = Subscription::query()->notOnGracePeriod()->get();
        $this->assertGreaterThanOrEqual(2, $notGraceSubscriptions->count());
        $this->assertFalse($notGraceSubscriptions->contains($graceSubscription));
    }

    /**
     * Test cannot renew subscription that has reached contract limit.
     */
    public function test_cannot_renew_beyond_contract_cycles()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        $subscription = $user->newSubscription('default', $plan->id)
            ->contractCycles(1)
            ->saveAndInvoice([], true);

        $subscription->paymentConfirmation();
        $subscription->renew(); // Completes contract and cancels

        // Verify it's canceled
        $this->assertEquals(SubscriptionStatus::CANCELED, $subscription->status);
        $this->assertTrue($subscription->contractComplete());

        // Try to renew again - should throw exception because it's ended
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to renew canceled ended subscription');

        $subscription->renew();
    }

    /**
     * Test renewing a subscription clears the trial_ends_at date.
     */
    public function test_renew_clears_trial_ends_at()
    {
        // 1. Create a subscription that is currently on trial (or just ended trial)
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'trial_days' => 14,
            'interval' => 'month',
            'price' => 1000,
        ]);

        // Create subscription manually to simulate state just before renewal
        $trialEndAndExpires = Carbon::now()->subMinute(); // Just passed

        $subscription = Subscription::create([
            'type' => 'default',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::TRIALING,
            'trial_ends_at' => $trialEndAndExpires,
            'starts_at' => $trialEndAndExpires, // Billing starts when trial ends
            'expires_at' => $trialEndAndExpires, // Expires when billing period (trial) ends
        ]);

        // 2. Refresh to make sure
        $subscription->refresh();
        $this->assertEquals($trialEndAndExpires->toDateTimeString(), $subscription->trial_ends_at->toDateTimeString());

        // 3. Call renew()
        $subscription->renew();

        // 4. Assert trial_ends_at is NULL
        $this->assertNull($subscription->trial_ends_at, 'trial_ends_at should be null after renewal');

        // 5. Assert Invoice is generated
        $this->assertCount(1, $subscription->invoices()->get(), 'An invoice should be generated upon renewal');
    }
}
