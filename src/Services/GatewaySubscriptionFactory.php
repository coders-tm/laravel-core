<?php

namespace Coderstm\Services;

use Coderstm\Models\Subscription;
use Coderstm\Contracts\SubscriptionGateway;
use Coderstm\Services\Gateways\CommonSubscriptionGateway;
use Coderstm\Services\Gateways\GoCardlessSubscriptionGateway;

class GatewaySubscriptionFactory
{
    /**
     * Create a subscription gateway for the given subscription
     *
     * @param Subscription $subscription
     * @return \Coderstm\Contracts\SubscriptionGateway
     * @throws \Exception
     */
    public static function make(Subscription $subscription): SubscriptionGateway
    {
        $provider = $subscription->provider;

        switch ($provider) {
            case 'gocardless':
                return new GoCardlessSubscriptionGateway($subscription);
            default:
                return new CommonSubscriptionGateway($subscription);
        }
    }
}
