<?php

namespace Coderstm\Listeners\GoCardless;

use Coderstm\Events\SubscriptionPlanChanged;
use Coderstm\Services\GatewaySubscriptionFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SubscriptionChangeListener implements ShouldQueue
{
    public function __construct() {}

    public function handle(SubscriptionPlanChanged $event)
    {
        $subscription = $event->subscription;
        if ($subscription->provider !== 'gocardless') {
            return;
        }
        try {
            $service = GatewaySubscriptionFactory::make($subscription);
            $mandateId = $service->getProviderId();
            if (empty($mandateId)) {
                return;
            }
            $service->updatePlan($event->hasIntervalChanged(), $event->hasPriceChanged());
        } catch (\Throwable $e) {
            Log::error('Failed to update GoCardless subscription', ['error' => $e->getMessage(), 'subscription_id' => $subscription->id]);
        }
    }
}
