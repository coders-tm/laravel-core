<?php

namespace Coderstm\Listeners\Cashier;

use Illuminate\Contracts\Queue\ShouldQueue;
use Coderstm\Events\Cashier\SubscriptionProcessed;

class SubscriptionEventListener
{
    /**
     * Handle received Cashier webhooks.
     *
     * @param  \Coderstm\Events\Cashier\SubscriptionProcessed  $event
     * @return void
     */
    public function handle(SubscriptionProcessed $event)
    {
        $subscription = $event->subscription;
        $subscription->syncLatestInvoice();
    }
}
