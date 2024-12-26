<?php

namespace Coderstm\Tests\Unit\Notifications;

use Coderstm\Tests\TestCase;
use App\Models\User;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\Notification;
use Coderstm\Notifications\SubscriptionCanceledNotification;

class SubscriptionCanceledNotificationTest extends TestCase
{
    public function testSubscriptionCanceledNotification()
    {
        Notification::fake();

        $user = User::factory()->create();
        $subscription = Subscription::factory()->canceled()->create(['user_id' => $user->id]);

        $notification = new SubscriptionCanceledNotification($subscription);

        Notification::send($user, $notification);

        Notification::assertSentTo(
            $user,
            SubscriptionCanceledNotification::class,
            function ($notification, $channels) use ($user, $subscription) {
                return $notification->subject === $subscription->renderNotification('user:subscription-canceled')->subject &&
                    $notification->message === $subscription->renderNotification('user:subscription-canceled')->content;
            }
        );
    }
}
