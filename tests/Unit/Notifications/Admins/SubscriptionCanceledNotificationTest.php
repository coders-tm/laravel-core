<?php

namespace Coderstm\Tests\Unit\Notifications\Admins;

use Coderstm\Tests\TestCase;
use App\Models\User;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\Notification;
use Coderstm\Notifications\Admins\SubscriptionCanceledNotification;

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
                return $notification->subject === $subscription->renderNotification('admin:subscription-cancel')->subject &&
                    $notification->message === $subscription->renderNotification('admin:subscription-cancel')->content;
            }
        );
    }
}
