<?php

namespace Tests\Feature;

use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Feature;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_features_are_created_on_subscription_creation()
    {
        // Create a feature
        $feature = Feature::factory()->create([
            'slug' => 'test-feature',
            'type' => 'integer',
            'resetable' => true,
        ]);

        // Create a plan without auto-syncing features
        $plan = new Plan([
            'label' => 'Test Plan',
            'slug' => 'test-plan',
            'description' => 'A test plan',
            'is_active' => true,
            'default_interval' => 'month',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'trial_days' => 0,
            'options' => null,
        ]);
        $plan->save();

        // Attach feature to plan
        $plan->features()->attach($feature, ['value' => 10]);

        // Create a subscription
        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
        ]);

        // Set status to active after creation (since generateInvoice sets it to pending)
        $subscription->update(['status' => 'active']);

        // Refresh the subscription to get the latest data
        $subscription->refresh();

        // Check if subscription features were created
        $this->assertCount(1, $subscription->features);

        $subscriptionFeature = $subscription->features->first();
        $this->assertEquals($feature->slug, $subscriptionFeature->slug);
        $this->assertEquals($feature->label, $subscriptionFeature->label);
        $this->assertEquals(10, $subscriptionFeature->value);
        $this->assertEquals(0, $subscriptionFeature->used);
    }

    public function test_can_use_feature_works_with_subscription_features()
    {
        // Create a feature
        $feature = Feature::factory()->create([
            'slug' => 'test-feature',
            'type' => 'integer',
            'resetable' => true,
        ]);

        // Create a plan without auto-syncing features
        $plan = new Plan([
            'label' => 'Test Plan',
            'slug' => 'test-plan-2',
            'description' => 'A test plan',
            'is_active' => true,
            'default_interval' => 'month',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'trial_days' => 0,
            'options' => null,
        ]);
        $plan->save();

        // Attach feature to plan
        $plan->features()->attach($feature, ['value' => 10]);

        // Create a subscription
        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
        ]);

        // Set status to active after creation (since generateInvoice sets it to pending)
        $subscription->update(['status' => 'active']);

        // Refresh the subscription to get the latest data
        $subscription->refresh();

        // Test canUseFeature
        $this->assertTrue($subscription->canUseFeature($feature->slug));
    }

    public function test_record_feature_usage_works_with_subscription_features()
    {
        // Create a feature
        $feature = Feature::factory()->create([
            'slug' => 'test-feature',
            'type' => 'integer',
            'resetable' => true,
        ]);

        // Create a plan without auto-syncing features
        $plan = new Plan([
            'label' => 'Test Plan',
            'slug' => 'test-plan-3',
            'description' => 'A test plan',
            'is_active' => true,
            'default_interval' => 'month',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'trial_days' => 0,
            'options' => null,
        ]);
        $plan->save();

        // Attach feature to plan
        $plan->features()->attach($feature, ['value' => 10]);

        // Create a subscription
        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
        ]);

        // Set status to active after creation (since generateInvoice sets it to pending)
        $subscription->update(['status' => 'active']);

        // Refresh the subscription to get the latest data
        $subscription->refresh();

        // Record feature usage
        $subscription->recordFeatureUsage($feature->slug, 3);

        // Check usage
        $this->assertEquals(3, $subscription->getFeatureUsage($feature->slug));
        $this->assertEquals(7, $subscription->getFeatureRemainings($feature->slug));
    }

    public function test_reduce_feature_usage_works_with_subscription_features()
    {
        // Create a feature
        $feature = Feature::factory()->create([
            'slug' => 'test-feature',
            'type' => 'integer',
            'resetable' => true,
        ]);

        // Create a plan without auto-syncing features
        $plan = new Plan([
            'label' => 'Test Plan',
            'slug' => 'test-plan-4',
            'description' => 'A test plan',
            'is_active' => true,
            'default_interval' => 'month',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'trial_days' => 0,
            'options' => null,
        ]);
        $plan->save();

        // Attach feature to plan
        $plan->features()->attach($feature, ['value' => 10]);

        // Create a subscription
        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
        ]);

        // Set status to active after creation (since generateInvoice sets it to pending)
        $subscription->update(['status' => 'active']);

        // Refresh the subscription to get the latest data
        $subscription->refresh();

        // Record feature usage
        $subscription->recordFeatureUsage($feature->slug, 5);

        // Reduce feature usage
        $subscription->reduceFeatureUsage($feature->slug, 2);

        // Check usage
        $this->assertEquals(3, $subscription->getFeatureUsage($feature->slug));
        $this->assertEquals(7, $subscription->getFeatureRemainings($feature->slug));
    }

    public function test_reset_usages_works_with_subscription_features()
    {
        // Create a feature
        $feature = Feature::factory()->create([
            'slug' => 'test-feature',
            'type' => 'integer',
            'resetable' => true,
        ]);

        // Create a plan without auto-syncing features
        $plan = new Plan([
            'label' => 'Test Plan',
            'slug' => 'test-plan-5',
            'description' => 'A test plan',
            'is_active' => true,
            'default_interval' => 'month',
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
            'trial_days' => 0,
            'options' => null,
        ]);
        $plan->save();

        // Attach feature to plan
        $plan->features()->attach($feature, ['value' => 10]);

        // Create a subscription
        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
        ]);

        // Set status to active after creation (since generateInvoice sets it to pending)
        $subscription->update(['status' => 'active']);

        // Refresh the subscription to get the latest data
        $subscription->refresh();

        // Record feature usage
        $subscription->recordFeatureUsage($feature->slug, 5);

        // Reset usages
        $subscription->resetUsages();

        // Check usage
        $this->assertEquals(0, $subscription->getFeatureUsage($feature->slug));
        $this->assertEquals(10, $subscription->getFeatureRemainings($feature->slug));
    }

    public function test_cannot_use_feature_with_expired_subscription()
    {
        $subscription = Subscription::factory()->create([
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);

        // Get the first subscription feature
        $subscriptionFeature = $subscription->features->first();
        $this->assertNotNull($subscriptionFeature, 'No subscription features found');

        // Even though the feature has remaining usage, it should not be usable
        // because the subscription itself is expired/invalid
        $this->assertFalse($subscription->canUseFeature($subscriptionFeature->slug));
    }
}
