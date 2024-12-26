<?php

namespace Coderstm\Tests\Feature;

use Coderstm\Models\User;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Notifications\UserSignupNotification;
use Coderstm\Notifications\SubscriptionUpgradeNotification;
use Coderstm\Notifications\SubscriptionRenewedNotification;
use Coderstm\Notifications\SubscriptionExpiredNotification;
use Coderstm\Notifications\SubscriptionDowngradeNotification;
use Coderstm\Notifications\SubscriptionCancelNotification;
use Coderstm\Notifications\SubscriptionCanceledNotification;
use Coderstm\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\Notification;

class NotificationShortcodeTest extends FeatureTestCase
{
    public function testUserSignupNotification()
    {
        $user = User::factory()->create();
        Notification::fake();

        Subscription::factory()->create(['user_id' => $user->id]);

        $user->notify(new UserSignupNotification($user));

        Notification::assertSentTo(
            [$user],
            UserSignupNotification::class,
            function ($notification, $channels) use ($user) {
                $this->assertStringContainsString($user->first_name, $notification->message);
                $this->assertStringContainsString($user->subscription()->plan->label, $notification->message);
                return true;
            }
        );
    }

    public function testSubscriptionUpgradeNotification()
    {
        $subscription = Subscription::factory()->create();
        Notification::fake();

        $subscription->oldPlan = Plan::factory()->make();

        $subscription->user->notify(new SubscriptionUpgradeNotification($subscription));

        Notification::assertSentTo(
            [$subscription->user],
            SubscriptionUpgradeNotification::class,
            function ($notification, $channels) use ($subscription) {
                $this->assertStringContainsString($subscription->oldPlan->label, $notification->message);
                return true;
            }
        );
    }

    public function testSubscriptionRenewedNotification()
    {
        $subscription = Subscription::factory()->create();
        Notification::fake();

        $subscription->user->notify(new SubscriptionRenewedNotification($subscription));

        Notification::assertSentTo(
            [$subscription->user],
            SubscriptionRenewedNotification::class,
            function ($notification, $channels) use ($subscription) {
                $this->assertStringContainsString($subscription->plan->label, $notification->message);
                return true;
            }
        );
    }

    public function testSubscriptionExpiredNotification()
    {
        $subscription = Subscription::factory()->create();
        Notification::fake();

        $subscription->user->notify(new SubscriptionExpiredNotification($subscription));

        Notification::assertSentTo(
            [$subscription->user],
            SubscriptionExpiredNotification::class,
            function ($notification, $channels) use ($subscription) {
                $this->assertStringContainsString($subscription->plan->label, $notification->message);
                return true;
            }
        );
    }

    public function testSubscriptionDowngradeNotification()
    {
        $subscription = Subscription::factory()->create();
        Notification::fake();

        $subscription->oldPlan = Plan::factory()->make();

        $subscription->user->notify(new SubscriptionDowngradeNotification($subscription));

        Notification::assertSentTo(
            [$subscription->user],
            SubscriptionDowngradeNotification::class,
            function ($notification, $channels) use ($subscription) {
                $this->assertStringContainsString($subscription->oldPlan->label, $notification->message);
                return true;
            }
        );
    }

    public function testSubscriptionCancelNotification()
    {
        $subscription = Subscription::factory()->create();
        Notification::fake();

        $subscription->user->notify(new SubscriptionCancelNotification($subscription));

        Notification::assertSentTo(
            [$subscription->user],
            SubscriptionCancelNotification::class,
            function ($notification, $channels) use ($subscription) {
                $this->assertStringContainsString($subscription->plan->label, $notification->message);
                return true;
            }
        );
    }

    public function testSubscriptionCanceledNotification()
    {
        $subscription = Subscription::factory()->create();
        Notification::fake();

        $subscription->user->notify(new SubscriptionCanceledNotification($subscription));

        Notification::assertSentTo(
            [$subscription->user],
            SubscriptionCanceledNotification::class,
            function ($notification, $channels) use ($subscription) {
                $this->assertStringContainsString($subscription->plan->label, $notification->message);
                return true;
            }
        );
    }
}
