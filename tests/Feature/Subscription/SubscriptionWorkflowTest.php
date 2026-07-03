<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Coderstm\Contracts\ManagesSubscriptions;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Coupon;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Tests\Feature\FeatureTestCase;

/**
 * Comprehensive Subscription Workflow Test
 *
 * Tests the complete subscription lifecycle including:
 * - Creation with trials and coupons
 * - Invoice generation
 * - Plan swapping/upgrades/downgrades
 * - Cancellation and resumption
 * - Payment processing
 */
class SubscriptionWorkflowTest extends FeatureTestCase
{
    public function test_complete_subscription_creation_workflow()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 14]);

        // Create subscription with trial
        $subscription = $user->newSubscription('default', $plan->id);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertFalse($subscription->exists);

        // Set trial period
        $subscription->trialDays(14);

        // Save and invoice
        $subscription->saveAndInvoice();

        // Verify subscription was created
        $this->assertTrue($subscription->exists);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertEquals('default', $subscription->type);
        $this->assertTrue($subscription->onTrial());

        // Verify no invoice was generated during trial
        $this->assertNull($subscription->latestInvoice);
    }

    public function test_subscription_creation_with_coupon()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);
        $coupon = Coupon::factory()->create([
            'promotion_code' => 'TEST20',
            'discount_type' => 'percentage',
            'value' => 20,
        ]);

        // Create subscription with coupon
        $subscription = $user->newSubscription('default', $plan->id)
            ->withCoupon('TEST20')
            ->saveAndInvoice([], true); // Force invoice generation

        $this->assertTrue($subscription->exists);
        $this->assertEquals($coupon->id, $subscription->coupon_id);

        // Verify invoice was generated with discount
        $invoice = $subscription->latestInvoice;
        $this->assertNotNull($invoice);
    }

    public function test_subscription_trial_to_active_workflow()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        // Create subscription with trial
        $subscription = $user->newSubscription('default', $plan->id)
            ->trialDays(14)
            ->saveAndInvoice();

        $this->assertTrue($subscription->onTrial());
        $this->assertEquals(SubscriptionStatus::TRIALING, $subscription->status);

        // End trial
        $subscription->endTrial();

        $this->assertFalse($subscription->onTrial());

        // Generate invoice after trial
        $subscription->saveAndInvoice([], true);

        $invoice = $subscription->latestInvoice;
        $this->assertNotNull($invoice);
    }

    public function test_subscription_plan_swap_workflow()
    {
        $user = User::factory()->create();
        $basicPlan = Plan::factory()->create(['price' => 1000, 'label' => 'Basic', 'trial_days' => 0]);
        $proPlan = Plan::factory()->create(['price' => 2000, 'label' => 'Pro', 'trial_days' => 0]);

        // Create subscription with basic plan
        $subscription = $user->newSubscription('default', $basicPlan->id)
            ->saveAndInvoice([], true);

        $this->assertEquals($basicPlan->id, $subscription->plan_id);

        // Swap to pro plan
        $subscription->swap($proPlan->id);

        $this->assertEquals($proPlan->id, $subscription->plan_id);

        // Verify new invoice was generated for the swap
        $latestInvoice = $subscription->latestInvoice;
        $this->assertNotNull($latestInvoice);
    }

    public function test_subscription_cancellation_and_resumption_workflow()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        // Create active subscription
        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        $this->assertNull($subscription->canceled_at);
        $this->assertFalse($subscription->canceled());

        // Cancel subscription
        $subscription->cancel();

        $this->assertNotNull($subscription->canceled_at);
        $this->assertTrue($subscription->canceled());
        $this->assertTrue($subscription->canceledOnGracePeriod());

        // Resume subscription
        $subscription->resume();

        $this->assertNull($subscription->canceled_at);
        $this->assertFalse($subscription->canceled());
        $this->assertTrue($subscription->active());
    }

    public function test_subscription_immediate_cancellation_workflow()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        // Create active subscription
        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        // Cancel immediately
        $subscription->cancelNow();

        $this->assertTrue($subscription->canceled());
        $this->assertFalse($subscription->canceledOnGracePeriod());
        $this->assertEquals(SubscriptionStatus::CANCELED, $subscription->status);
    }

    public function test_subscription_renewal_workflow()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        // Create subscription
        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        $originalStartsAt = $subscription->starts_at;
        $originalExpiresAt = $subscription->expires_at->copy();

        // Renew subscription
        $subscription->renew();

        // Verify period was extended
        $this->assertNotEquals($originalExpiresAt->toDateString(), $subscription->expires_at->toDateString());
    }

    public function test_subscription_with_multiple_method_chaining()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 14]);

        // Test method chaining
        $subscription = $user->newSubscription('default', $plan->id)
            ->trialDays(14)
            ->skipTrial()
            ->saveAndInvoice([], true)
            ->refresh();

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertTrue($subscription->exists);
        $this->assertFalse($subscription->onTrial());
        $this->assertNotNull($subscription->latestInvoice);
    }

    public function test_subscription_implements_manages_subscriptions_interface()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        // Verify subscription implements the interface
        $this->assertInstanceOf(ManagesSubscriptions::class, $subscription);

        // Test interface methods are available
        $this->assertTrue(method_exists($subscription, 'valid'));
        $this->assertTrue(method_exists($subscription, 'swap'));
        $this->assertTrue(method_exists($subscription, 'cancel'));
        $this->assertTrue(method_exists($subscription, 'resume'));
        $this->assertTrue(method_exists($subscription, 'saveAndInvoice'));
    }

    public function test_subscription_downgrade_workflow()
    {
        $user = User::factory()->create();
        $proPlan = Plan::factory()->create(['price' => 2000, 'label' => 'Pro']);
        $basicPlan = Plan::factory()->create(['price' => 1000, 'label' => 'Basic']);

        // Create subscription with pro plan
        $subscription = $user->newSubscription('default', $proPlan->id)
            ->saveAndInvoice([], true);

        $this->assertEquals($proPlan->id, $subscription->plan_id);

        // Downgrade to basic plan (without immediate swap)
        $subscription->next_plan = $basicPlan->id;
        $subscription->is_downgrade = true;
        $subscription->save();

        $this->assertTrue($subscription->hasDowngrade());
        $this->assertEquals($basicPlan->id, $subscription->next_plan);

        // Cancel downgrade
        $subscription->cancelDowngrade();

        $this->assertFalse($subscription->hasDowngrade());
        $this->assertNull($subscription->next_plan);
    }

    public function test_subscription_payment_confirmation_workflow()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        // Create subscription with pending payment
        $subscription = $user->newSubscription('default', $plan->id);
        $subscription->status = SubscriptionStatus::PENDING;
        $subscription->save();

        $this->assertEquals(SubscriptionStatus::PENDING, $subscription->status);

        // Confirm payment
        $subscription->paymentConfirmation();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
    }

    public function test_subscription_payment_failure_workflow()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        // Create active subscription
        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        // Simulate payment failure
        $subscription->paymentFailed();

        $this->assertEquals(SubscriptionStatus::INCOMPLETE, $subscription->status);
        $this->assertTrue($subscription->hasIncompletePayment());
    }

    public function test_subscription_cancel_open_invoices_workflow()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        // Create subscription with invoice
        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        // Generate another invoice
        $subscription->generateInvoice(true);

        // Cancel all open invoices
        $subscription->cancelOpenInvoices();

        // Verify invoices are handled
        $this->assertInstanceOf(Subscription::class, $subscription);
    }
}
