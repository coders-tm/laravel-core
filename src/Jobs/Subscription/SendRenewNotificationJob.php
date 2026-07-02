<?php

namespace Coderstm\Jobs\Subscription;

use Coderstm\Notifications\SubscriptionRenewedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRenewNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }

    public function handle(): void
    {
        if ($this->subscription->user) {
            $this->subscription->user->notify(new SubscriptionRenewedNotification($this->subscription));
        }
    }
}
