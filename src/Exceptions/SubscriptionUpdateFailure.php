<?php

namespace Coderstm\Exceptions;

use Coderstm\Models\Subscription;
use Exception;

class SubscriptionUpdateFailure extends Exception
{
    /**
     * Create a new SubscriptionUpdateFailure instance.
     *
     * @param  Subscription  $subscription
     * @return static
     */
    public static function incompleteSubscription($subscription)
    {
        return new static(
            "The subscription \"{$subscription->plan_id}\" cannot be updated because its payment is incomplete."
        );
    }
}
