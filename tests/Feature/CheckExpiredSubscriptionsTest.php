<?php

namespace Coderstm\Tests\Feature;

use Coderstm\Models\Log;
use Coderstm\Models\Subscription;
use Coderstm\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Coderstm\Notifications\SubscriptionExpiredNotification;
use Coderstm\Notifications\Admins\SubscriptionExpiredNotification as AdminsSubscriptionExpiredNotification;
use PHPUnit\Framework\Attributes\Test;

class CheckExpiredSubscriptionsTest extends FeatureTestCase
{
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
            'logable_type' => get_class($subscription),
            'logable_id' => $subscription->id,
            'message' => 'Notification for expired subscriptions has been successfully sent.'
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
            'logable_type' => get_class($subscription),
            'logable_id' => $subscription->id,
            'status' => Log::STATUS_ERROR,
            'message' => 'Failed to send notification',
        ]);
    }
}
