<?php

namespace Coderstm\Jobs\Subscription;

use Coderstm\Models\Subscription;
use Coderstm\Notifications\SubscriptionRenewedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to send subscription renewed notification to the user.
 */
class SendRenewNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The subscription instance.
     *
     * @var Subscription
     */
    public $subscription;

    /**
     * Create a new job instance.
     *
     * @param  Subscription  $subscription
     * @return void
     */
    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Execute the job.
     *
     * Sends the renewal notification to the subscription's user.
     */
    public function handle(): void
    {
        if ($this->subscription->user) {
            $this->subscription->user->notify(
                new SubscriptionRenewedNotification($this->subscription)
            );
        }
    }
}
