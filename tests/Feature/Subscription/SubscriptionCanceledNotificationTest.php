<?php

namespace Tests\Feature;

use Coderstm\Models\Subscription;
use Coderstm\Models\User;
use Coderstm\Notifications\SubscriptionCanceledNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionCanceledNotificationTest extends TestCase
{
    public function test_subscription_cancellation_sends_notification()
    {
        Notification::fake();

        $user = User::factory()->create();
        $subscription = Subscription::factory()->canceled()->create(['user_id' => $user->id]);

        $notification = new SubscriptionCanceledNotification($subscription);

        Notification::send($user, $notification);

        Notification::assertSentTo(
            $user,
            SubscriptionCanceledNotification::class,
            function ($notification, $channels) use ($subscription) {
                return $notification->subject === $subscription->renderNotification('user:subscription-canceled')->subject &&
                    $notification->message === $subscription->renderNotification('user:subscription-canceled')->content;
            }
        );
    }
}
