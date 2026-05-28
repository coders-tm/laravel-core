<?php

namespace Coderstm\Jobs\Subscription;

use Coderstm\Models\Subscription;
use Coderstm\Notifications\SubscriptionRenewedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRenewNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Subscription $subscription;

    public function __construct(Subscription $subscription)
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
