<?php

namespace Tests\Unit\Notifications\Admins;

use App\Models\User;
use Coderstm\Models\Subscription;
use Coderstm\Notifications\Admins\SubscriptionCanceledNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionCanceledNotificationTest extends TestCase
{
    public function test_subscription_canceled_notification()
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
                return $notification->subject === $subscription->renderNotification('admin:subscription-cancel')->subject &&
                    $notification->message === $subscription->renderNotification('admin:subscription-cancel')->content;
            }
        );
    }
}
