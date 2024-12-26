<?php

namespace Coderstm\Tests\Notifications;

use Coderstm\Tests\TestCase;
use App\Models\User;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\Notification;
use Coderstm\Notifications\SubscriptionExpiredNotification;

class SubscriptionExpiredNotificationTest extends TestCase
{
    public function testNotificationConstruct()
    {
        Notification::fake();

        $user = User::factory()->create();
        $expiration = now()->subDay();
        $subscription = Subscription::factory()->create(['user_id' => $user->id, 'expires_at' => $expiration]);

        $this->assertEquals($subscription->expires_at->format('Y-m-d'), $expiration->format('Y-m-d'));

        $notification = new SubscriptionExpiredNotification($subscription);

        Notification::send($user, $notification);

        Notification::assertSentTo(
            $user,
            SubscriptionExpiredNotification::class,
            function ($notification, $channels) use ($user, $subscription) {
                return $notification->subject === $subscription->renderNotification('user:subscription-expired')->subject &&
                    $notification->message === $subscription->renderNotification('user:subscription-expired')->content;
            }
        );
    }
}
