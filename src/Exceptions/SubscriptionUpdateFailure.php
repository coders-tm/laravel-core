<?php

namespace Coderstm\Exceptions;

use Coderstm\Models\Subscription;
use Exception;

class SubscriptionUpdateFailure extends Exception
{
    public static function incompleteSubscription(Subscription $subscription)
    {
        return new static("The subscription \"{$subscription->plan_id}\" cannot be updated because its payment is incomplete.");
    }
}
