<?php

namespace Coderstm\Actions\Subscription;

use Carbon\Carbon;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;
use Coderstm\Services\Period;

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

        $period = new Period(
            $subscription->plan->interval->value,
            $subscription->plan->interval_count,
            $subscription->starts_at ?? Carbon::now()
        );

        $subscription->fill([
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => $period->getEndDate(),
            'canceled_at' => null,
        ])->save();

        return $subscription;
    }
}
