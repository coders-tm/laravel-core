<?php

namespace Tests\Feature\Subscription;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Models\Subscription;
use Coderstm\Notifications\Admins\SubscriptionExpiredNotification as AdminsSubscriptionExpiredNotification;
use Coderstm\Notifications\SubscriptionExpiredNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class CheckExpiredSubscriptionsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    #[Test]
    public function it_sends_notifications_for_expired_subscriptions()
    {
        // Arrange
        $subscription = Subscription::factory()->create(['expires_at' => now()->subDay()]);
        Notification::fake();

        // Act
        $this->artisan('coderstm:subscriptions-expired')
            ->assertExitCode(0);

        // Assert: Verify notification sent
        Notification::assertSentTo($subscription->user, SubscriptionExpiredNotification::class);

        // Assert that the notification was sent to the correct admin email
        Notification::assertSentTo(
            new AnonymousNotifiable,
            AdminsSubscriptionExpiredNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === [config('coderstm.admin_email') => config('app.name')];
            }
        );

        // Assert: Verify log creation
        $this->assertDatabaseHas('logs', [
            'type' => 'expired-notification',
            // Use configured subscription model class to avoid static override flakiness
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
            'message' => 'Notification for expired subscriptions has been successfully sent.',
        ]);
    }

    #[Test]
    public function it_respects_filter_hook_that_blocks_user_notification_but_still_sends_admin()
    {
        $subscription = Subscription::factory()->create(['expires_at' => now()->subDay()]);
        Notification::fake();

        add_filter('subscription.expired.should_send', function () {
            return false;
        }, 10, 3);

        $this->artisan('coderstm:subscriptions-expired')
            ->assertExitCode(0);

        Notification::assertNotSentTo(
            [$subscription->user],
            SubscriptionExpiredNotification::class
        );

        Notification::assertSentTo(
            new AnonymousNotifiable,
            AdminsSubscriptionExpiredNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === [config('coderstm.admin_email') => config('app.name')];
            }
        );

        $this->assertDatabaseMissing('logs', [
            'type' => 'expired-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_logs_an_error_when_notification_fails()
    {
        // Arrange: Mock notification to throw an exception
        $subscription = Subscription::factory()->create(['expires_at' => now()->subDay()]);
        Notification::fake();
        Notification::shouldReceive('send')
            ->andThrow(new \Exception('Failed to send notification'));

        // Act
        $this->artisan('coderstm:subscriptions-expired')
            ->assertExitCode(0);

        // Assert: Verify error log created
        $this->assertDatabaseHas('logs', [
            'type' => 'expired-notification',
            // Use configured subscription model class to avoid static override flakiness
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
            'status' => Log::STATUS_ERROR,
            'message' => 'Failed to send notification',
        ]);
    }
}
