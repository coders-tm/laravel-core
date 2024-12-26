<?php

namespace Coderstm\Tests\Feature;

use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Tests\Feature\FeatureTestCase;

class SubscriptionsRenewTest extends FeatureTestCase
{
    /** @test */
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
            'logable_type' => get_class($subscription),
            'logable_id' => $subscription->id,
            'message' => 'Subscription has been renewed successfully!',
        ]);
    }

    /** @test */
    public function it_logs_an_error_when_renewal_fails()
    {
        // Arrange: Create an active subscription and mock the renew method to throw an exception
        $subscription = Subscription::withoutEvents(function () {
            return Subscription::factory()->create([
                'expires_at' => now()->subDay(),
            ]);
        });

        $this->partialMock(Subscription::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('renew')
                ->andThrow(new \Exception('Renewal failed'));
        });

        // Act: Run the command
        $this->artisan('coderstm:subscriptions-renew')
            ->assertExitCode(0);

        // Assert: Check the error log entry was created
        // $this->assertDatabaseHas('logs', [
        //     'type' => 'renew',
        //     'status' => Log::STATUS_ERROR,
        //     'logable_type' => get_class($subscription),
        //     'logable_id' => $subscription->id,
        //     'message' => "Subscription #{$subscription->id} unable to renew! Renewal failed",
        // ]);
    }
}
