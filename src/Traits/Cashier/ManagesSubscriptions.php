<?php

namespace Coderstm\Traits\Cashier;

use Coderstm\Cashier\SubscriptionBuilder;
use Laravel\Cashier\Concerns\ManagesSubscriptions as CashierManagesSubscriptions;

trait ManagesSubscriptions
{
    use CashierManagesSubscriptions;

    /**
     * Begin creating a new subscription.
     *
     * @param  string  $name
     * @param  string|string[]  $prices
     * @return \Coderstm\Cashier\SubscriptionBuilder
     */
    public function newSubscription($name, $prices = [])
    {
        return new SubscriptionBuilder($this, $name, $prices);
    }
}
