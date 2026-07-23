<?php

namespace Tests\Feature;

use App\Models\User;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Feature;
use Coderstm\Models\Subscription\Plan;
use Tests\TestCase;

/**
 * Integration test to verify the complete fix for subscription feature reset issue.
 * This test simulates the exact scenario described in the GitHub issue.
 */
class SubscriptionFeatureResetIntegrationTest extends TestCase
{
    public function test_subscription_features_are_reset_when_plan_is_swapped_via_api_route()
    {
        // Create features that would be in different plans
        $basicFeature = Feature::factory()->create([
            'slug' => 'basic-users',
            'label' => 'Basic Users',
            'type' => 'integer',
        ]);

        $proFeature = Feature::factory()->create([
            'slug' => 'pro-analytics',
            'label' => 'Pro Analytics',
            'type' => 'boolean',
        ]);

        // Create Basic Plan with 5 users
        $basicPlan = Plan::create([
            'label' => 'Basic Plan',
            'price' => 1000, // $10
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $basicPlan->features()->attach([
            $basicFeature->id => ['value' => 5],
        ]);

        // Create Pro Plan with 20 users and pro analytics
        $proPlan = Plan::create([
            'label' => 'Pro Plan',
            'price' => 5000, // $50
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $proPlan->features()->attach([
            $basicFeature->id => ['value' => 20],
            $proFeature->id => ['value' => 1],
        ]);

        // Create user with subscription to Basic Plan
        $user = User::factory()->create();
        $subscription = $user->newSubscription('default', $basicPlan->id)
            ->saveWithoutInvoice();

        // Verify initial state
        $this->assertEquals($basicPlan->id, $subscription->plan_id);
        $this->assertCount(1, $subscription->features);
        $this->assertEquals(5, $subscription->getFeatureValue('basic-users'));
        $this->assertNull($subscription->getFeatureValue('pro-analytics'));

        // User uses 3 out of 5 basic users
        $subscription->recordFeatureUsage('basic-users', 3);
        $this->assertEquals(3, $subscription->getFeatureUsage('basic-users'));

        // ===== UPGRADE TO PRO PLAN (simulating /api/subscription/subscribe or /api/users/{user}/subscription) =====
        $subscription->swap($proPlan->id, 'monthly', false);

        // Refresh to get the latest data from database
        $subscription->refresh();

        // VERIFICATION: Features should be reset from the swapped plan
        $this->assertEquals($proPlan->id, $subscription->plan_id, 'Plan should be updated to Pro');

        // Should have 2 features now (basic-users and pro-analytics)
        $this->assertCount(2, $subscription->features, 'Should have 2 features from Pro plan');

        // Feature values should be from Pro plan
        $this->assertEquals(20, $subscription->getFeatureValue('basic-users'), 'Basic users should be 20 from Pro plan');
        $this->assertEquals(1, $subscription->getFeatureValue('pro-analytics'), 'Pro analytics should be available');

        // Usage should not be reset to 0
        $this->assertEquals(3, $subscription->getFeatureUsage('basic-users'), 'Usage should not be reset after swap');
    }

    public function test_subscription_features_are_reset_when_downgrading_from_pro_to_basic()
    {
        // Create features
        $storageFeature = Feature::factory()->create([
            'slug' => 'storage-gb',
            'label' => 'Storage GB',
            'type' => 'integer',
        ]);

        $premiumSupportFeature = Feature::factory()->create([
            'slug' => 'premium-support',
            'label' => 'Premium Support',
            'type' => 'boolean',
        ]);

        // Create Pro Plan with 100GB storage and premium support
        $proPlan = Plan::create([
            'label' => 'Pro Plan',
            'price' => 5000,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $proPlan->features()->attach([
            $storageFeature->id => ['value' => 100],
            $premiumSupportFeature->id => ['value' => 1],
        ]);

        // Create Basic Plan with only 10GB storage
        $basicPlan = Plan::create([
            'label' => 'Basic Plan',
            'price' => 1000,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $basicPlan->features()->attach([
            $storageFeature->id => ['value' => 10],
        ]);

        // Create user with Pro subscription
        $user = User::factory()->create();
        $subscription = $user->newSubscription('default', $proPlan->id)
            ->saveWithoutInvoice();

        // Use 50GB of storage
        $subscription->recordFeatureUsage('storage-gb', 50);
        $this->assertEquals(50, $subscription->getFeatureUsage('storage-gb'));

        // ===== DOWNGRADE TO BASIC PLAN =====
        $subscription->swap($basicPlan->id, 'monthly', false);
        $subscription->refresh();

        // VERIFICATION: Features should be reset from the downgraded plan
        $this->assertEquals($basicPlan->id, $subscription->plan_id, 'Plan should be downgraded to Basic');

        // Should only have 1 feature (storage-gb), premium-support should be removed
        $this->assertCount(1, $subscription->features, 'Should only have 1 feature from Basic plan');

        // Storage should be reduced to 10GB
        $this->assertEquals(10, $subscription->getFeatureValue('storage-gb'), 'Storage should be 10GB from Basic plan');

        // Premium support should be removed
        $this->assertNull($subscription->getFeatureValue('premium-support'), 'Premium support should be removed');

        // Usage should not be reset to 0
        $this->assertEquals(50, $subscription->getFeatureUsage('storage-gb'), 'Usage should not be reset after downgrade');
    }

    public function test_scheduled_downgrade_syncs_features_on_renewal()
    {
        // Create features
        $apiCallsFeature = Feature::factory()->create([
            'slug' => 'api-calls',
            'label' => 'API Calls',
            'type' => 'integer',
            'resetable' => true,
        ]);

        $webhooksFeature = Feature::factory()->create([
            'slug' => 'webhooks',
            'label' => 'Webhooks',
            'type' => 'boolean',
        ]);

        // Pro Plan
        $proPlan = Plan::create([
            'label' => 'Pro Plan',
            'price' => 5000,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $proPlan->features()->attach([
            $apiCallsFeature->id => ['value' => 10000],
            $webhooksFeature->id => ['value' => 1],
        ]);

        // Basic Plan (no webhooks)
        $basicPlan = Plan::create([
            'label' => 'Basic Plan',
            'price' => 1000,
            'interval' => 'month',
            'interval_count' => 1,
        ]);
        $basicPlan->features()->attach([
            $apiCallsFeature->id => ['value' => 1000],
        ]);

        // Create Pro subscription
        $user = User::factory()->create();
        $subscription = $user->newSubscription('default', $proPlan->id)
            ->saveWithoutInvoice();

        // Use some API calls
        $subscription->recordFeatureUsage('api-calls', 5000);

        // Schedule a downgrade (simulating the downgrade controller method)
        $subscription->update([
            'next_plan' => $basicPlan->id,
            'is_downgrade' => true,
            'expires_at' => now()->subDay(), // Subscription expired, ready for renewal
        ]);

        // ===== RENEW SUBSCRIPTION (which applies the downgrade) =====
        $subscription->renew();
        $subscription->refresh();

        // VERIFICATION: Features should be synced from the downgraded plan
        $this->assertEquals($basicPlan->id, $subscription->plan_id, 'Plan should be downgraded to Basic on renewal');

        // Should only have 1 feature
        $this->assertCount(1, $subscription->features, 'Should only have 1 feature from Basic plan');

        // API calls should be reduced
        $this->assertEquals(1000, $subscription->getFeatureValue('api-calls'), 'API calls should be 1000 from Basic plan');

        // Webhooks should be removed
        $this->assertNull($subscription->getFeatureValue('webhooks'), 'Webhooks should be removed');

        // Usage should be reset
        $this->assertEquals(0, $subscription->getFeatureUsage('api-calls'), 'Usage should be reset on renewal');
    }
}
