<?php

namespace Coderstm\Actions\Subscription;

use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;

class ResumeSubscription
{
    /**
     * Resume subscription.
     *
     * @param  Subscription  $subscription
     * @return Subscription
     */
    public function execute($subscription)
    {
        if (! $subscription->canceledOnGracePeriod()) {
            throw new \LogicException('Unable to resume subscription that is not within grace period.');
        }

        $subscription->guardAgainstIncomplete();

        $subscription->fill([
            'status' => SubscriptionStatus::ACTIVE,
            'cancels_at' => null,
        ])->save();

        return $subscription;
    }
}
