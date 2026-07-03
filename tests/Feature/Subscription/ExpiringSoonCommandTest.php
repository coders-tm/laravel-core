<?php

namespace Tests\Feature\Subscription;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Models\Subscription;
use Coderstm\Notifications\SubscriptionExpiringNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ExpiringSoonCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_sends_expiring_7_days_notification()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(7)->startOfDay(),
        ]);
        Notification::fake();

        $this->artisan('coderstm:subscriptions-expiring-soon')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $subscription->user,
            SubscriptionExpiringNotification::class,
            function ($notification) {
                return true;
            }
        );

        $this->assertDatabaseHas('logs', [
            'type' => 'expiring-7-days-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_sends_expiring_2_days_notification()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(2)->startOfDay(),
        ]);
        Notification::fake();

        $this->artisan('coderstm:subscriptions-expiring-soon')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $subscription->user,
            SubscriptionExpiringNotification::class,
            function ($notification) {
                return true;
            }
        );

        $this->assertDatabaseHas('logs', [
            'type' => 'expiring-2-days-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_sends_expiring_1_day_notification()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDay()->startOfDay(),
        ]);
        Notification::fake();

        $this->artisan('coderstm:subscriptions-expiring-soon')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $subscription->user,
            SubscriptionExpiringNotification::class,
            function ($notification) {
                return true;
            }
        );

        $this->assertDatabaseHas('logs', [
            'type' => 'expiring-1-day-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_sends_when_action_already_exists()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(7)->startOfDay(),
        ]);

        $subscription->attachAction('expiring-7-days-notification');

        Notification::fake();

        $this->artisan('coderstm:subscriptions-expiring-soon')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $subscription->user,
            SubscriptionExpiringNotification::class,
            function ($notification) {
                return true;
            }
        );

        $this->assertDatabaseHas('logs', [
            'type' => 'expiring-7-days-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_respects_filter_hook_that_blocks_notification()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(7)->startOfDay(),
        ]);
        Notification::fake();

        add_filter('subscription.expiring_soon.should_send', function () {
            return false;
        }, 10, 4);

        $this->artisan('coderstm:subscriptions-expiring-soon')
            ->assertExitCode(0);

        Notification::assertNotSentTo(
            [$subscription->user],
            SubscriptionExpiringNotification::class
        );

        $this->assertDatabaseMissing('logs', [
            'type' => 'expiring-7-days-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_still_sends_when_filter_hook_allows()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(7)->startOfDay(),
        ]);
        Notification::fake();

        add_filter('subscription.expiring_soon.should_send', function () {
            return true;
        }, 10, 4);

        $this->artisan('coderstm:subscriptions-expiring-soon')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $subscription->user,
            SubscriptionExpiringNotification::class,
            function ($notification) {
                return true;
            }
        );

        $this->assertDatabaseHas('logs', [
            'type' => 'expiring-7-days-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_logs_error_when_notification_fails()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(7)->startOfDay(),
        ]);
        Notification::fake();
        Notification::shouldReceive('send')
            ->andThrow(new \Exception('Failed to send notification'));

        $this->artisan('coderstm:subscriptions-expiring-soon')
            ->assertExitCode(0);

        $this->assertDatabaseHas('logs', [
            'type' => 'expiring-7-days-notification',
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
            'status' => Log::STATUS_ERROR,
            'message' => 'Failed to send notification',
        ]);
    }
}
