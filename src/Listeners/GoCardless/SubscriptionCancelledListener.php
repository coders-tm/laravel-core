<?php

namespace Coderstm\Listeners\GoCardless;

use Coderstm\Events\SubscriptionCancelled;
use Coderstm\Services\GatewaySubscriptionFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SubscriptionCancelledListener implements ShouldQueue
{
    public function __construct() {}

    public function handle(SubscriptionCancelled $event)
    {
        $subscription = $event->subscription;
        if ($subscription->provider !== 'gocardless') {
            return;
        }
        try {
            $gateway = GatewaySubscriptionFactory::make($subscription);
            $gateway->cancel(['reason' => 'Customer canceled subscription']);
        } catch (\Throwable $e) {
            Log::error('Failed to cancel GoCardless subscription', ['error' => $e->getMessage(), 'subscription_id' => $subscription->id]);
        }
    }
}
