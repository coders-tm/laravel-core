<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Feature;
use Coderstm\Models\Subscription\Plan;
use Tests\Feature\FeatureTestCase;

class SubscriptionFeatureSyncTest extends FeatureTestCase
{
    public function test_swap_subscription_syncs_feature_values_from_new_plan()
    {
        $user = User::factory()->create();
        $planA = Plan::factory()->create(['trial_days' => 0]);
        $planB = Plan::factory()->create(['trial_days' => 0]);

        // Create a feature
        $feature = Feature::factory()->create([
            'slug' => 'test-limit',
            'type' => 'integer',
            'resetable' => true,
            'label' => 'Test Limit',
        ]);

        // Attach feature to Plan A with value 10
        $planA->features()->attach($feature->id, ['value' => 10]);
        // Attach feature to Plan B with value 20
        $planB->features()->attach($feature->id, ['value' => 20]);

        // Create subscription on Plan A
        $subscription = $user->newSubscription('default', $planA->id)
            ->saveAndInvoice([], true);

        // Verify initial state
        $this->assertEquals(10, $subscription->getFeatureValue('test-limit'), 'Initial feature value should be 10');
        $this->assertEquals(0, $subscription->getFeatureUsage('test-limit'), 'Initial feature usage should be 0');

        // Record usage
        $subscription->recordFeatureUsage('test-limit', 5);
        $this->assertEquals(5, $subscription->getFeatureUsage('test-limit'), 'Usage should be 5');

        // Swap to Plan B
        $subscription->swap($planB->id);

        // Verify state after swap
        $this->assertEquals(0, $subscription->getFeatureUsage('test-limit'), 'Usage should be reset to 0 after swap');

        // Feature value should be updated to Plan B's value (20)
        $this->assertEquals(20, $subscription->getFeatureValue('test-limit'), 'Feature value should update to 20 after swap');
    }
}
