<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Tests\TestCase;

class SubscriptionRenewalTest extends TestCase
{
    public function test_renewal_extends_expires_at_by_plan_interval()
    {
        // Arrange: Create a monthly plan
        $plan = Plan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $user = User::factory()->create();

        // Create subscription with specific dates
        $originalStartsAt = Carbon::parse('2025-01-01');
        $originalExpiresAt = Carbon::parse('2025-02-01');

        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => $originalStartsAt,
            'expires_at' => $originalExpiresAt,
        ]);
        $subscription->save();

        // Act: Renew the subscription
        $subscription->renew();

        // Assert: expires_at should be extended by 1 month from the original expiry
        $this->assertNotEquals(
            $originalExpiresAt->format('Y-m-d H:i:s'),
            $subscription->expires_at->format('Y-m-d H:i:s'),
            'expires_at should change after renewal'
        );

        // The new expires_at should be approximately 1 month after the original expires_at
        $expectedNewExpiry = $originalExpiresAt->copy()->addMonth();
        $this->assertEquals(
            $expectedNewExpiry->format('Y-m-d'),
            $subscription->expires_at->format('Y-m-d'),
            'expires_at should be extended by 1 month from original expiry'
        );
    }

    public function test_renewal_with_different_interval_counts()
    {
        // Arrange: Create a 3-month plan
        $plan = Plan::factory()->create([
            'interval' => 'month',
            'interval_count' => 3,
            'price' => 3000,
        ]);

        $user = User::factory()->create();

        $originalExpiresAt = Carbon::parse('2025-01-01');

        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::parse('2024-10-01'),
            'expires_at' => $originalExpiresAt,
        ]);
        $subscription->save();

        // Act: Renew the subscription
        $subscription->renew();

        // Assert: expires_at should be extended by 3 months
        $expectedNewExpiry = $originalExpiresAt->copy()->addMonths(3);
        $this->assertEquals(
            $expectedNewExpiry->format('Y-m-d'),
            $subscription->expires_at->format('Y-m-d'),
            'expires_at should be extended by 3 months from original expiry'
        );
    }

    public function test_renewal_with_yearly_plan()
    {
        // Arrange: Create a yearly plan
        $plan = Plan::factory()->create([
            'interval' => 'year',
            'interval_count' => 1,
            'price' => 12000,
        ]);

        $user = User::factory()->create();

        $originalExpiresAt = Carbon::parse('2025-01-01');

        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::parse('2024-01-01'),
            'expires_at' => $originalExpiresAt,
        ]);
        $subscription->save();

        // Act: Renew the subscription
        $subscription->renew();

        // Assert: expires_at should be extended by 1 year
        $expectedNewExpiry = $originalExpiresAt->copy()->addYear();
        $this->assertEquals(
            $expectedNewExpiry->format('Y-m-d'),
            $subscription->expires_at->format('Y-m-d'),
            'expires_at should be extended by 1 year from original expiry'
        );
    }

    public function test_renewal_with_next_plan_updates_billing_fields()
    {
        // Create two plans with different intervals and contract settings
        $monthlyPlan = Plan::factory()->create([
            'label' => 'Monthly',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'is_contract' => false,
            'contract_cycles' => null,
        ]);

        $quarterlyContractPlan = Plan::factory()->create([
            'label' => 'Quarterly Contract',
            'interval' => 'month',
            'interval_count' => 3,
            'price' => 2500,
            'is_contract' => true,
            'contract_cycles' => 4, // 4 quarters = 1 year contract
        ]);

        $user = User::factory()->create();

        // Create subscription with monthly plan
        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $monthlyPlan->id,
            'billing_interval' => 'month',
            'billing_interval_count' => 1,
            'total_cycles' => null,
            'current_cycle' => 3,
            'status' => 'active',
            'starts_at' => Carbon::parse('2025-01-01'),
            'expires_at' => Carbon::parse('2025-04-01'),
        ]);
        $subscription->save();

        // Schedule downgrade to quarterly contract plan
        $subscription->next_plan = $quarterlyContractPlan->id;
        $subscription->is_downgrade = true;
        $subscription->save();

        // Verify initial state
        $this->assertEquals('month', $subscription->billing_interval);
        $this->assertEquals(1, $subscription->billing_interval_count);
        $this->assertNull($subscription->total_cycles);
        $this->assertEquals(3, $subscription->current_cycle);
        $this->assertEquals($monthlyPlan->id, $subscription->plan_id);

        // Renew the subscription (should apply the next plan)
        $subscription->renew();

        // Reload to get fresh data
        $subscription->refresh();

        // Verify billing fields are updated from next plan
        $this->assertEquals('month', $subscription->billing_interval, 'Billing interval should be month from new plan');
        $this->assertEquals(3, $subscription->billing_interval_count, 'Billing interval count should be 3 from new plan');
        $this->assertEquals(4, $subscription->total_cycles, 'Total cycles should be 4 from contract plan');
        $this->assertEquals(1, $subscription->current_cycle, 'Current cycle should be reset to 1 (incremented after reset to 0)');

        // Verify plan was switched
        $this->assertEquals($quarterlyContractPlan->id, $subscription->plan_id, 'Plan should be switched to quarterly plan');
        $this->assertNull($subscription->next_plan, 'Next plan should be cleared');
        $this->assertFalse((bool) $subscription->is_downgrade, 'Is downgrade flag should be cleared');

        // Verify the new period uses the new billing interval (3 months)
        $expectedExpiresAt = Carbon::parse('2025-04-01')->addMonths(3);
        $this->assertEquals(
            $expectedExpiresAt->format('Y-m-d'),
            $subscription->expires_at->format('Y-m-d'),
            'Expires at should be 3 months from original expiry (using new billing interval)'
        );
    }

    public function test_renewal_uses_plan_grace_period_days()
    {
        // Arrange: Create a plan with custom grace period
        $plan = Plan::factory()->withGracePeriod(14)->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $user = User::factory()->create();

        $originalExpiresAt = Carbon::parse('2025-01-01');

        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::parse('2024-12-01'),
            'expires_at' => $originalExpiresAt,
        ]);
        $subscription->save();

        // Act: Renew the subscription
        $now = Carbon::now();
        $subscription->renew();

        // Assert: ends_at (grace period end) should be 14 days from now
        // (using plan's grace_period_days instead of config default)
        $expectedGraceEnd = $now->copy()->addDays(14);

        $this->assertNotNull($subscription->ends_at, 'ends_at should be set after renewal');

        // Compare dates (allowing 1 second tolerance for test execution time)
        $this->assertEquals(
            $expectedGraceEnd->format('Y-m-d H:i'),
            $subscription->ends_at->format('Y-m-d H:i'),
            'Grace period end (ends_at) should be 14 days from now (using plan grace_period_days)'
        );
    }

    public function test_renewal_falls_back_to_config_grace_period_when_plan_uses_default()
    {
        // Arrange: Create a plan with default grace_period_days (0 days from factory - no grace period)
        $plan = Plan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            // grace_period_days will use factory default of 0
        ]);

        $user = User::factory()->create();

        $originalExpiresAt = Carbon::parse('2025-01-01');

        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::parse('2024-12-01'),
            'expires_at' => $originalExpiresAt,
        ]);
        $subscription->save();

        // Act: Renew the subscription
        $subscription->renew();

        // Assert: Should have no grace period (ends_at should be null when grace_period_days is 0)
        $this->assertNull($subscription->ends_at, 'ends_at should be null when grace_period_days is 0 (no grace period, expires immediately)');
    }

    public function test_renewal_with_zero_grace_period_expires_immediately()
    {
        // Arrange: Create a plan with explicit 0 grace period
        $plan = Plan::factory()->withGracePeriod(0)->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $user = User::factory()->create();

        $originalExpiresAt = Carbon::parse('2025-01-01');

        $subscription = new Subscription([
            'user_id' => $user->id,
            'type' => 'default',
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::parse('2024-12-01'),
            'expires_at' => $originalExpiresAt,
        ]);
        $subscription->save();

        // Act: Renew the subscription
        $subscription->renew();

        // Assert: Should have no grace period (subscription expires immediately)
        $this->assertNull($subscription->ends_at, 'ends_at should be null when grace_period_days is 0');
        $this->assertNotNull($subscription->expires_at, 'expires_at should still be set');
        $this->assertEquals(SubscriptionStatus::EXPIRED, $subscription->status, 'Subscription should be marked as expired');
    }
}
