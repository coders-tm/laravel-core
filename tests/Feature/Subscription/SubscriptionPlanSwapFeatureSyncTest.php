<?php

namespace Tests\Feature;

use App\Models\User;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Feature;
use Coderstm\Models\Subscription\Plan;
use Tests\TestCase;

class SubscriptionPlanSwapFeatureSyncTest extends TestCase
{
    public function test_features_are_synced_from_new_plan_when_swapping()
    {
        // Create two features
        $feature1 = Feature::factory()->create([
            'slug' => 'storage',
            'label' => 'Storage',
            'type' => 'integer',
            'resetable' => true,
        ]);

        $feature2 = Feature::factory()->create([
            'slug' => 'users',
            'label' => 'Users',
            'type' => 'integer',
            'resetable' => true,
        ]);

        $feature3 = Feature::factory()->create([
            'slug' => 'premium-support',
            'label' => 'Premium Support',
            'type' => 'boolean',
            'resetable' => false,
        ]);

        // Create basic plan with storage and users features (manually, without factory auto-attach)
        $basicPlan = Plan::create([
            'label' => 'Basic',
            'price' => 1000,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $basicPlan->features()->attach([
            $feature1->id => ['value' => 100], // 100GB storage
            $feature2->id => ['value' => 5],   // 5 users
        ]);

        // Create pro plan with storage, users, and premium-support
        $proPlan = Plan::create([
            'label' => 'Pro',
            'price' => 2000,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $proPlan->features()->attach([
            $feature1->id => ['value' => 500],  // 500GB storage
            $feature2->id => ['value' => 25],   // 25 users
            $feature3->id => ['value' => 1],    // Premium support
        ]);

        // Create user and subscription with basic plan
        $user = User::factory()->create();
        $subscription = $user->newSubscription('default', $basicPlan->id)
            ->saveWithoutInvoice();

        // Verify initial features from basic plan
        $this->assertCount(2, $subscription->features);
        $this->assertEquals(100, $subscription->getFeatureValue('storage'));
        $this->assertEquals(5, $subscription->getFeatureValue('users'));
        $this->assertNull($subscription->getFeatureValue('premium-support'));

        // Record some usage on the basic plan
        $subscription->recordFeatureUsage('storage', 50);
        $subscription->recordFeatureUsage('users', 3);
        $this->assertEquals(50, $subscription->getFeatureUsage('storage'));
        $this->assertEquals(3, $subscription->getFeatureUsage('users'));

        // Swap to pro plan
        $subscription->swap($proPlan->id, 'monthly', false); // Don't invoice

        // Refresh subscription to get updated features
        $subscription->refresh();

        // Verify features are synced from new plan
        $this->assertCount(3, $subscription->features, 'Should have 3 features after swap');

        // Verify feature values are updated from pro plan
        $this->assertEquals(500, $subscription->getFeatureValue('storage'), 'Storage should be 500GB from pro plan');
        $this->assertEquals(25, $subscription->getFeatureValue('users'), 'Users should be 25 from pro plan');
        $this->assertEquals(1, $subscription->getFeatureValue('premium-support'), 'Premium support should be present');

        // Verify usage is not reset
        $this->assertEquals(50, $subscription->getFeatureUsage('storage'), 'Storage usage should not be reset');
        $this->assertEquals(3, $subscription->getFeatureUsage('users'), 'Users usage should not be reset');
    }

    public function test_old_features_are_removed_when_swapping_to_plan_without_them()
    {
        // Create features
        $feature1 = Feature::factory()->create([
            'slug' => 'advanced-analytics',
            'label' => 'Advanced Analytics',
            'type' => 'boolean',
        ]);

        $feature2 = Feature::factory()->create([
            'slug' => 'basic-reports',
            'label' => 'Basic Reports',
            'type' => 'boolean',
        ]);

        // Create pro plan with advanced analytics
        $proPlan = Plan::create([
            'label' => 'Pro',
            'price' => 2000,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $proPlan->features()->attach([
            $feature1->id => ['value' => 1],
            $feature2->id => ['value' => 1],
        ]);

        // Create basic plan with only basic reports
        $basicPlan = Plan::create([
            'label' => 'Basic',
            'price' => 1000,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $basicPlan->features()->attach([
            $feature2->id => ['value' => 1],
        ]);

        // Create user and subscription with pro plan
        $user = User::factory()->create();
        $subscription = $user->newSubscription('default', $proPlan->id)
            ->saveWithoutInvoice();

        // Verify initial features from pro plan
        $this->assertCount(2, $subscription->features);
        $this->assertEquals(1, $subscription->getFeatureValue('advanced-analytics'));
        $this->assertEquals(1, $subscription->getFeatureValue('basic-reports'));

        // Downgrade to basic plan
        $subscription->swap($basicPlan->id, 'monthly', false);

        // Refresh subscription
        $subscription->refresh();

        // Verify advanced analytics is removed, only basic reports remain
        $this->assertCount(1, $subscription->features, 'Should only have 1 feature after downgrade');
        $this->assertNull($subscription->getFeatureValue('advanced-analytics'), 'Advanced analytics should be removed');
        $this->assertEquals(1, $subscription->getFeatureValue('basic-reports'), 'Basic reports should remain');
    }

    public function test_features_are_synced_on_upgrade()
    {
        // Create features
        $storageFeature = Feature::factory()->create([
            'slug' => 'storage',
            'label' => 'Storage',
            'type' => 'integer',
        ]);

        // Basic plan with 10GB
        $basicPlan = Plan::create([
            'label' => 'Basic',
            'price' => 10,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $basicPlan->features()->attach([$storageFeature->id => ['value' => 10]]);

        // Pro plan with 100GB
        $proPlan = Plan::create([
            'label' => 'Pro',
            'price' => 20,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $proPlan->features()->attach([$storageFeature->id => ['value' => 100]]);

        // Create subscription on basic plan
        $user = User::factory()->create();
        $subscription = $user->newSubscription('default', $basicPlan->id)
            ->saveWithoutInvoice();

        $this->assertEquals(10, $subscription->getFeatureValue('storage'));

        // Use some storage
        $subscription->recordFeatureUsage('storage', 8);
        $this->assertEquals(8, $subscription->getFeatureUsage('storage'));

        // Upgrade to pro plan
        $subscription->swap($proPlan->id, 'monthly', false);
        $subscription->refresh();

        // Verify storage limit is updated to 100 and usage is not reset
        $this->assertEquals(100, $subscription->getFeatureValue('storage'), 'Storage should be upgraded to 100GB');
        $this->assertEquals(8, $subscription->getFeatureUsage('storage'), 'Usage should not be reset on upgrade');
    }

    public function test_features_are_synced_when_downgrade_is_applied_on_renewal()
    {
        // Create features
        $storageFeature = Feature::factory()->create([
            'slug' => 'storage',
            'label' => 'Storage',
            'type' => 'integer',
            'resetable' => true,
        ]);

        $premiumFeature = Feature::factory()->create([
            'slug' => 'premium-support',
            'label' => 'Premium Support',
            'type' => 'boolean',
        ]);

        // Pro plan with 100GB storage and premium support
        $proPlan = Plan::create([
            'label' => 'Pro',
            'price' => 20,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $proPlan->features()->attach([
            $storageFeature->id => ['value' => 100],
            $premiumFeature->id => ['value' => 1],
        ]);

        // Basic plan with only 10GB storage (no premium support)
        $basicPlan = Plan::create([
            'label' => 'Basic',
            'price' => 10,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $basicPlan->features()->attach([
            $storageFeature->id => ['value' => 10],
        ]);

        // Create subscription on pro plan
        $user = User::factory()->create();
        $subscription = $user->newSubscription('default', $proPlan->id)
            ->saveWithoutInvoice();

        // Verify initial features
        $this->assertCount(2, $subscription->features);
        $this->assertEquals(100, $subscription->getFeatureValue('storage'));
        $this->assertEquals(1, $subscription->getFeatureValue('premium-support'));

        // Use some storage
        $subscription->recordFeatureUsage('storage', 50);
        $this->assertEquals(50, $subscription->getFeatureUsage('storage'));

        // Schedule a downgrade to basic plan
        $subscription->update([
            'next_plan' => $basicPlan->id,
            'is_downgrade' => true,
            'expires_at' => now()->subDay(), // Make it ready for renewal
        ]);

        // Renew the subscription (which should apply the downgrade)
        $subscription->renew();

        // Refresh to get updated features
        $subscription->refresh();

        // Verify features are synced from basic plan
        $this->assertEquals($basicPlan->id, $subscription->plan_id, 'Plan should be downgraded to basic');
        $this->assertCount(1, $subscription->features, 'Should only have 1 feature after downgrade');
        $this->assertEquals(10, $subscription->getFeatureValue('storage'), 'Storage should be downgraded to 10GB');
        $this->assertNull($subscription->getFeatureValue('premium-support'), 'Premium support should be removed');
        $this->assertEquals(0, $subscription->getFeatureUsage('storage'), 'Usage should be reset on renewal');
    }

    public function test_swap_updates_billing_interval_and_contract_fields()
    {
        // Create user
        $user = User::factory()->create();

        // Create monthly non-contract plan
        $monthlyPlan = Plan::create([
            'label' => 'Monthly Plan',
            'price' => 1000,
            'interval' => 'month',
            'interval_count' => 1,
            'is_contract' => false,
            'contract_cycles' => null,
        ]);

        // Create yearly contract plan (12 month contract)
        $yearlyContractPlan = Plan::create([
            'label' => 'Yearly Contract',
            'price' => 10000,
            'interval' => 'year',
            'interval_count' => 1,
            'is_contract' => true,
            'contract_cycles' => 12, // 12 billing cycles
        ]);

        // Create quarterly plan
        $quarterlyPlan = Plan::create([
            'label' => 'Quarterly Plan',
            'price' => 2500,
            'interval' => 'month',
            'interval_count' => 3,
            'is_contract' => false,
            'contract_cycles' => null,
        ]);

        // Create subscription with monthly plan
        $subscription = $user->newSubscription('default', $monthlyPlan->id)
            ->saveWithoutInvoice();

        // Verify initial state
        $this->assertEquals('month', $subscription->billing_interval);
        $this->assertEquals(1, $subscription->billing_interval_count);
        $this->assertNull($subscription->total_cycles);
        $this->assertEquals(0, $subscription->current_cycle);

        // Swap to yearly contract plan
        $subscription->swap($yearlyContractPlan->id, 'monthly', false);
        $subscription->refresh();

        // Verify billing interval and contract fields are updated
        $this->assertEquals('year', $subscription->billing_interval, 'Billing interval should be updated to year');
        $this->assertEquals(1, $subscription->billing_interval_count, 'Billing interval count should be 1');
        $this->assertEquals(12, $subscription->total_cycles, 'Total cycles should be set from plan contract_cycles');
        $this->assertEquals(0, $subscription->current_cycle, 'Current cycle should be reset to 0');

        // Record some cycles
        $subscription->current_cycle = 5;
        $subscription->save();

        // Swap to quarterly plan
        $subscription->swap($quarterlyPlan->id, 'monthly', false);
        $subscription->refresh();

        // Verify all fields are updated again
        $this->assertEquals('month', $subscription->billing_interval, 'Billing interval should be month');
        $this->assertEquals(3, $subscription->billing_interval_count, 'Billing interval count should be 3');
        $this->assertNull($subscription->total_cycles, 'Total cycles should be null (no contract)');
        $this->assertEquals(0, $subscription->current_cycle, 'Current cycle should be reset to 0 on swap');
    }

    public function test_force_swap_updates_billing_interval_and_contract_fields()
    {
        // Create user
        $user = User::factory()->create();

        // Create monthly plan
        $monthlyPlan = Plan::create([
            'label' => 'Monthly Plan',
            'price' => 1000,
            'interval' => 'month',
            'interval_count' => 1,
            'is_contract' => false,
            'contract_cycles' => null,
        ]);

        // Create contract plan
        $contractPlan = Plan::create([
            'label' => 'Contract Plan',
            'price' => 5000,
            'interval' => 'month',
            'interval_count' => 1,
            'is_contract' => true,
            'contract_cycles' => 6, // 6 month contract
        ]);

        // Create subscription
        $subscription = $user->newSubscription('default', $monthlyPlan->id)
            ->saveWithoutInvoice();

        // Simulate some progress
        $subscription->current_cycle = 3;
        $subscription->save();

        // Admin force swap to contract plan
        $subscription->forceSwap($contractPlan->id, 'monthly', false);
        $subscription->refresh();

        // Verify all fields are updated
        $this->assertEquals('month', $subscription->billing_interval);
        $this->assertEquals(1, $subscription->billing_interval_count);
        $this->assertEquals(6, $subscription->total_cycles, 'Total cycles should be set from contract plan');
        $this->assertEquals(0, $subscription->current_cycle, 'Current cycle should be reset to 0');
        $this->assertEquals($contractPlan->id, $subscription->plan_id);
    }
}
