<?php

namespace Tests\Feature\Subscription;

use Coderstm\Coderstm;
use Coderstm\Models\Log;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Notifications\SubscriptionGraceNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class GraceNotificationCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_sends_grace_notification_to_subscriptions_on_grace_period()
    {
        $plan = Plan::factory()->withGracePeriod(7)->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
            'expires_at' => now()->subDays(2),
            'ends_at' => now()->addDays(5),
        ]);

        Notification::fake();

        $this->artisan('coderstm:subscriptions-grace-notification')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $subscription->user,
            SubscriptionGraceNotification::class,
            function ($notification) {
                return true;
            }
        );

        $today = now()->format('Y-m-d');
        $this->assertDatabaseHas('logs', [
            'type' => "grace-notification-{$today}",
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_does_not_send_to_subscriptions_not_on_grace_period()
    {
        $subscription = Subscription::factory()->create([
            'expires_at' => now()->addDays(10),
            'ends_at' => null,
        ]);

        Notification::fake();

        $this->artisan('coderstm:subscriptions-grace-notification')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_sends_when_action_already_exists()
    {
        $plan = Plan::factory()->withGracePeriod(7)->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
            'expires_at' => now()->subDays(2),
            'ends_at' => now()->addDays(5),
        ]);

        $today = now()->format('Y-m-d');
        $subscription->attachAction("grace-notification-{$today}");

        Notification::fake();

        $this->artisan('coderstm:subscriptions-grace-notification')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $subscription->user,
            SubscriptionGraceNotification::class,
            function ($notification) {
                return true;
            }
        );

        $today = now()->format('Y-m-d');
        $this->assertDatabaseHas('logs', [
            'type' => "grace-notification-{$today}",
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_respects_filter_hook_that_blocks_notification()
    {
        $plan = Plan::factory()->withGracePeriod(7)->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
            'expires_at' => now()->subDays(2),
            'ends_at' => now()->addDays(5),
        ]);

        Notification::fake();

        add_filter('subscription.grace_notification.should_send', function () {
            return false;
        }, 10, 3);

        $this->artisan('coderstm:subscriptions-grace-notification')
            ->assertExitCode(0);

        Notification::assertNotSentTo(
            [$subscription->user],
            SubscriptionGraceNotification::class
        );

        $today = now()->format('Y-m-d');
        $this->assertDatabaseMissing('logs', [
            'type' => "grace-notification-{$today}",
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_still_sends_when_filter_hook_allows()
    {
        $plan = Plan::factory()->withGracePeriod(7)->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
            'expires_at' => now()->subDays(2),
            'ends_at' => now()->addDays(5),
        ]);

        Notification::fake();

        add_filter('subscription.grace_notification.should_send', function () {
            return true;
        }, 10, 3);

        $this->artisan('coderstm:subscriptions-grace-notification')
            ->assertExitCode(0);

        Notification::assertSentTo(
            $subscription->user,
            SubscriptionGraceNotification::class,
            function ($notification) {
                return true;
            }
        );

        $today = now()->format('Y-m-d');
        $this->assertDatabaseHas('logs', [
            'type' => "grace-notification-{$today}",
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
        ]);
    }

    #[Test]
    public function it_logs_error_when_grace_notification_fails()
    {
        $plan = Plan::factory()->withGracePeriod(7)->create([
            'interval' => 'month',
            'interval_count' => 1,
            'price' => 1000,
        ]);

        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
            'expires_at' => now()->subDays(2),
            'ends_at' => now()->addDays(5),
        ]);

        Notification::fake();
        Notification::shouldReceive('send')
            ->andThrow(new \Exception('Failed to send notification'));

        $this->artisan('coderstm:subscriptions-grace-notification')
            ->assertExitCode(0);

        $today = now()->format('Y-m-d');
        $this->assertDatabaseHas('logs', [
            'type' => "grace-notification-{$today}",
            'logable_type' => Coderstm::$subscriptionModel,
            'logable_id' => $subscription->id,
            'status' => Log::STATUS_ERROR,
            'message' => 'Failed to send notification',
        ]);
    }
}
