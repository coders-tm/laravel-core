<?php

namespace Coderstm\Actions\Subscription;

use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;

class CancelSubscription
{
    /**
     * Cancel the subscription.
     *
     * @param  Subscription  $subscription
     * @return Subscription
     */
    public function execute($subscription)
    {
        if ($subscription->onTrial()) {
            $subscription->expires_at = $subscription->trial_ends_at;
        }

        $subscription->canceled_at = now();
        $subscription->save();

        return $subscription;
    }

    /**
     * Cancel subscription at a specific date.
     *
     * @param  Subscription  $subscription
     * @return Subscription
     */
    public function cancelAt($subscription, ?\DateTimeInterface $endsAt)
    {
        if ($endsAt instanceof \DateTimeInterface) {
            $subscription->expires_at = $endsAt->getTimestamp();
        }

        $subscription->status = SubscriptionStatus::CANCELED;
        $subscription->canceled_at = now();
        $subscription->save();

        return $subscription;
    }

    /**
     * Cancel subscription immediately.
     *
     * @param  Subscription  $subscription
     * @return Subscription
     */
    public function cancelNow($subscription)
    {
        $subscription->fill([
            'status' => SubscriptionStatus::CANCELED,
            'expires_at' => now(),
            'canceled_at' => now(),
        ])->save();

        return $subscription;
    }
}
