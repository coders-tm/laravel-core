<?php

namespace Coderstm\Tests\Unit;

use Coderstm\Tests\TestCase;
use Coderstm\Models\User;
use Coderstm\Models\Subscription;
use Coderstm\Notifications\SubscriptionCanceledNotification;
use Illuminate\Support\Facades\Notification;

class SubscriptionCanceledNotificationTest extends TestCase
{
    public function testSubscriptionCancellationSendsNotification()
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
