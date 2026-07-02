<?php

namespace Coderstm\Actions\Subscription;

use Coderstm\Contracts\SubscriptionStatus;

class CancelSubscription
{
    public function execute($subscription)
    {
        if ($subscription->onTrial()) {
            $subscription->expires_at = $subscription->trial_ends_at;
        }
        $subscription->canceled_at = now();
        $subscription->save();

        return $subscription;
    }

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

    public function cancelNow($subscription)
    {
        $subscription->fill(['status' => SubscriptionStatus::CANCELED, 'expires_at' => now(), 'canceled_at' => now()])->save();

        return $subscription;
    }
}
