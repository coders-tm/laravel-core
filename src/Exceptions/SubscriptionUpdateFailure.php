<?php

namespace Coderstm\Exceptions;

use Exception;

class SubscriptionUpdateFailure extends Exception
{
    public static function incompleteSubscription($subscription)
    {
        return new static("The subscription \"{$subscription->plan_id}\" cannot be updated because its payment is incomplete.");
    }
}
