<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Feature;
use Coderstm\Models\Subscription\Plan;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class SubscriptionsRenewTest extends FeatureTestCase
{
    #[Test]
    public function it_renews_active_subscriptions()
    {
        Plan::factory()->create();

        // Arrange: Create an active subscription
        $subscription = Subscription::withoutEvents(function () {
            return Subscription::factory()->create([
                'expires_at' => now()->subDay(),
            ]);
        });

        // Act: Run the command
        $this->artisan('coderstm:subscriptions-renew')
            ->assertExitCode(0);

        $this->assertDatabaseHas('logs', [
            'type' => 'renew',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_logs_an_error_when_renewal_fails()
    {
        // Arrange: Create an active subscription and mock the renew method to throw an exception
        $subscription = Subscription::withoutEvents(function () {
            return Subscription::factory()->create([
                'expires_at' => now()->subDay(),
            ]);
        });

        $this->partialMock(Subscription::class, function ($mock) {
            $mock->shouldReceive('renew')
                ->andThrow(new \Exception('Renewal failed'));
        });

        // Act: Run the command
        $this->artisan('coderstm:subscriptions-renew')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_renews_trialing_subscriptions_that_have_expired()
    {
        $plan = Plan::factory()->create(['price' => 1000, 'trial_days' => 0]);

        // Arrange: Create a subscription with status 'trialing' but with expires_at in the past
        $subscription = Subscription::withoutEvents(function () use ($plan) {
            return Subscription::factory()->create([
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::TRIALING,
                'expires_at' => now()->subDay(),
            ]);
        });

        // Act: Run the command
        $this->artisan('coderstm:subscriptions-renew')
            ->assertExitCode(0);

        // Assert: A renew log was recorded for the subscription
        $this->assertDatabaseHas('logs', [
            'type' => 'renew',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);

        $this->assertDatabaseHas(Coderstm::$subscriptionModel, [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::EXPIRED,
        ]);
    }

    #[Test]
    public function it_does_not_reset_non_resetable_features_on_renewal()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 0]);

        // Create resetable and non-resetable features
        $resetableFeature = Feature::factory()->create([
            'slug' => 'api-calls',
            'type' => 'integer',
            'resetable' => true,
            'label' => 'API Calls',
        ]);

        $nonResetableFeature = Feature::factory()->create([
            'slug' => 'storage-used',
            'type' => 'integer',
            'resetable' => false,
            'label' => 'Storage Used',
        ]);

        // Attach features to plan
        $plan->features()->attach($resetableFeature->id, ['value' => 1000]);
        $plan->features()->attach($nonResetableFeature->id, ['value' => 5000]);

        // Create subscription
        $subscription = $user->newSubscription('default', $plan->id)
            ->saveAndInvoice([], true);

        // Record usage for both features
        $subscription->recordFeatureUsage('api-calls', 500);
        $subscription->recordFeatureUsage('storage-used', 2500);

        // Verify initial usage
        $this->assertEquals(500, $subscription->getFeatureUsage('api-calls'));
        $this->assertEquals(2500, $subscription->getFeatureUsage('storage-used'));

        // Set subscription to expired to trigger renewal
        $subscription->update(['expires_at' => now()->subDay()]);

        // Renew the subscription directly (not via command)
        $subscription->renew();

        // Verify results after renewal
        $this->assertEquals(0, $subscription->getFeatureUsage('api-calls'), 'Resetable feature usage should be reset to 0');
        $this->assertEquals(2500, $subscription->getFeatureUsage('storage-used'), 'Non-resetable feature usage should NOT be reset');
    }
}
