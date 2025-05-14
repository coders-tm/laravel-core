<?php

namespace Coderstm\Tests\Feature;

use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Feature;
use Coderstm\Models\Subscription\Usage;

class SubscriptionHasFeatureTest extends FeatureTestCase
{
    public function test_can_use_feature()
    {
        $subscription = Subscription::factory()->create();
        $subscription->update(['status' => 'active']);
        $feature = Feature::factory()->countable()->create(['slug' => 'test-feature']);
        $subscription->plan->features()->attach($feature, ['value' => 10]);

        $this->assertTrue($subscription->canUseFeature('test-feature'));
    }

    public function test_cannot_use_expired_feature()
    {
        $subscription = Subscription::factory()->create();
        $feature = Feature::factory()->countable()->create(['slug' => 'test-feature']);
        $subscription->plan->features()->attach($feature, ['value' => 10]);
        Usage::create(['subscription_id' => $subscription->id, 'slug' => 'test-feature', 'used' => 7, 'reset_at' => now()->subDay()]);

        $this->assertFalse($subscription->canUseFeature('test-feature'));
    }

    public function test_record_feature_usage()
    {
        $subscription = Subscription::factory()->create();
        $feature = Feature::factory()->countable()->create(['slug' => 'test-feature']);
        $subscription->plan->features()->attach($feature, ['value' => 10]);

        $subscription->recordFeatureUsage('test-feature', 5);

        $this->assertEquals(5, $subscription->getFeatureUsage('test-feature'));

        $subscription->syncUsages();

        $this->assertEquals(5, $subscription->getFeatureUsage('test-feature'));

        $subscription->resetUsages();

        $this->assertEquals(0, $subscription->getFeatureUsage('test-feature'));
    }

    public function test_reduce_feature_usage()
    {
        $subscription = Subscription::factory()->create();
        $feature = Feature::factory()->countable()->create(['slug' => 'test-feature']);
        $subscription->plan->features()->attach($feature, ['value' => 10]);
        $subscription->recordFeatureUsage('test-feature', 5);

        $subscription->reduceFeatureUsage('test-feature', 3);

        $this->assertEquals(2, $subscription->getFeatureUsage('test-feature'));
    }
}
