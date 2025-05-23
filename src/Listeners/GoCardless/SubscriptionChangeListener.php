<?php

namespace Coderstm\Listeners\GoCardless;

use Coderstm\Events\SubscriptionPlanChanged;
use Coderstm\Services\GatewaySubscriptionFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SubscriptionChangeListener implements ShouldQueue
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
     * @param  \Coderstm\Events\SubscriptionPlanChanged  $event
     * @return void
     */
    public function handle(SubscriptionPlanChanged $event)
    {
        $subscription = $event->subscription;

        // Only process GoCardless subscriptions
        if ($subscription->provider !== 'gocardless') {
            return;
        }

        try {
            // Get the proper gateway service through the factory
            $service = GatewaySubscriptionFactory::make($subscription);

            // Get mandate ID to check if this is a valid subscription
            $mandateId = $service->getProviderId();
            if (empty($mandateId)) {
                return;
            }

            // Use the new updatePlan method
            $service->updatePlan(
                $event->hasIntervalChanged(),
                $event->hasPriceChanged()
            );
        } catch (\Exception $e) {
            Log::error('Failed to update GoCardless subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id
            ]);
        }
    }
}
