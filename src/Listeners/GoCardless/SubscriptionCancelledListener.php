<?php

namespace Coderstm\Listeners\GoCardless;

use Coderstm\Coderstm;
use Coderstm\Events\SubscriptionCancelled;
use Coderstm\Services\GatewaySubscriptionFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SubscriptionCancelledListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Coderstm\Events\SubscriptionCancelled  $event
     * @return void
     */
    public function handle(SubscriptionCancelled $event)
    {
        $subscription = $event->subscription;

        // Only process GoCardless subscriptions
        if ($subscription->provider !== 'gocardless') {
            return;
        }

        try {
            $gateway = GatewaySubscriptionFactory::make($subscription);

            // Cancel all active subscriptions for this mandate
            $gateway->cancel([
                'reason' => 'Customer canceled subscription',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel GoCardless subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id
            ]);
        }
    }
}
