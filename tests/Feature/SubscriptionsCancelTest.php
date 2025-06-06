<?php

namespace Coderstm\Tests\Feature;

use Coderstm\Models\Log;
use Coderstm\Models\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\Log as LogFacade;

class SubscriptionsCancelTest extends FeatureTestCase
{
    #[Test]
    public function it_cancels_subscriptions_and_deactivates_users()
    {
        // Arrange: Create an active subscription with a cancellation date in the past
        $subscription = Subscription::withoutEvents(function () {
            return Subscription::factory()->create([
                'cancels_at' => now()->subDay(),
                'status' => SubscriptionStatus::ACTIVE,
            ]);
        });

        // Act: Run the command
        $this->artisan('coderstm:subscriptions-cancel')
            ->assertExitCode(0);

        // Assert: Check that the subscription was canceled and the user was deactivated
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::CANCELED,
        ]);

        $this->assertDatabaseHas('logs', [
            'type' => 'canceled',
            'logable_type' => get_class($subscription),
            'logable_id' => $subscription->id,
            'message' => 'Subscription has been canceled successfully!',
        ]);
    }
}
