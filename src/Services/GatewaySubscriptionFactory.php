<?php

namespace Coderstm\Services;

use Coderstm\Contracts\SubscriptionGateway;
use Coderstm\Models\Subscription;
use Coderstm\Services\Gateways\CommonSubscriptionGateway;
use Coderstm\Services\Gateways\GoCardlessSubscriptionGateway;

class GatewaySubscriptionFactory
{
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
