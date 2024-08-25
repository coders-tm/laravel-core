<?php

namespace Coderstm\Exceptions;

use Exception;
use Coderstm\Models\Subscription;

class SubscriptionUpdateFailure extends Exception
{
    /**
     * Create a new SubscriptionUpdateFailure instance.
     *
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return static
     */
    public static function incompleteSubscription(Subscription $subscription)
    {
        return new static(
            "The subscription \"{$subscription->plan_id}\" cannot be updated because its payment is incomplete."
        );
    }
}
