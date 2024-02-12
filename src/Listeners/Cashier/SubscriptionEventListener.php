<?php

namespace Coderstm\Listeners\Cashier;

use Coderstm\Events\Cashier\SubscriptionProcessed;
use Coderstm\Models\Cashier\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        Invoice::createFromStripe($subscription->latestInvoice(), [
            'subscription_id' => $subscription->id
        ]);
    }
}
