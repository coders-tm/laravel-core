<?php

namespace Tests\Feature\Subscription;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Models\Subscription;
use Coderstm\Notifications\Admins\SubscriptionCanceledNotification as AdminsSubscriptionCanceledNotification;
use Coderstm\Notifications\SubscriptionCanceledNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CanceledSubscriptionCommandTest extends TestCase
{
    #[Test]
    public function it_sends_notifications_for_canceled_subscriptions()
    {
        $subscription = Subscription::factory()->canceled()->create([
            'expires_at' => now()->subDay(),
        ]);
        Notification::fake();

        $this->artisan('coderstm:subscriptions-canceled')
            ->assertExitCode(0);

        Notification::assertSentTo($subscription->user, SubscriptionCanceledNotification::class);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            AdminsSubscriptionCanceledNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === [config('coderstm.admin_email') => config('app.name')];
            }
        );

        $this->assertDatabaseHas('logs', [
            'type' => 'canceled-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
            'message' => 'Notification for canceled subscriptions has been successfully sent.',
        ]);
    }

    #[Test]
    public function it_respects_filter_hook_that_blocks_user_notification_but_still_sends_admin()
    {
        $subscription = Subscription::factory()->canceled()->create([
            'expires_at' => now()->subDay(),
        ]);
        Notification::fake();

        add_filter('subscription.canceled.should_send', function () {
            return false;
        }, 10, 3);

        $this->artisan('coderstm:subscriptions-canceled')
            ->assertExitCode(0);

        Notification::assertNotSentTo(
            [$subscription->user],
            SubscriptionCanceledNotification::class
        );

        Notification::assertSentTo(
            new AnonymousNotifiable,
            AdminsSubscriptionCanceledNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === [config('coderstm.admin_email') => config('app.name')];
            }
        );

        $this->assertDatabaseMissing('logs', [
            'type' => 'canceled-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_logs_an_error_when_notification_fails()
    {
        $subscription = Subscription::factory()->canceled()->create([
            'expires_at' => now()->subDay(),
        ]);
        Notification::fake();
        Notification::shouldReceive('send')
            ->andThrow(new \Exception('Failed to send notification'));

        $this->artisan('coderstm:subscriptions-canceled')
            ->assertExitCode(0);

        $this->assertDatabaseHas('logs', [
            'type' => 'canceled-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
            'status' => Log::STATUS_ERROR,
            'message' => 'Failed to send notification',
        ]);
    }
}
