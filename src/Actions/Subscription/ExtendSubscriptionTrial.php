<?php

namespace Coderstm\Actions\Subscription;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Subscription;

class ExtendSubscriptionTrial
{
    /**
     * Extend the subscription's trial to a specific date.
     *
     * @param  Subscription  $subscription
     * @return Subscription
     */
    public function extendTrial($subscription, CarbonInterface $date)
    {
        if (! $date->isFuture()) {
            throw new \InvalidArgumentException("Extending a subscription's trial requires a date in the future.");
        }

        $subscription->trial_ends_at = $date;
        $subscription->save();

        return $subscription;
    }

    /**
     * Set subscription's trial duration in days.
     *
     * @param  Subscription  $subscription
     * @return Subscription
     */
    public function trialDays($subscription, int $trialDays)
    {
        $subscription->trial_ends_at = Carbon::now()->addDays($trialDays);
        $subscription->status = SubscriptionStatus::TRIALING;
        $subscription->save();

        return $subscription;
    }

    /**
     * Set subscription's trial ending date.
     *
     * @param  Subscription  $subscription
     * @param  mixed  $trialUntil
     * @return Subscription
     */
    public function trialUntil($subscription, $trialUntil)
    {
        if (is_string($trialUntil)) {
            $trialUntil = Carbon::parse($trialUntil);
        } elseif ($trialUntil instanceof \DateTimeInterface && ! $trialUntil instanceof Carbon) {
            $trialUntil = Carbon::instance($trialUntil);
        }

        $subscription->trial_ends_at = $trialUntil;
        $subscription->status = SubscriptionStatus::TRIALING;
        $subscription->save();

        return $subscription;
    }

    /**
     * End subscription's trial.
     *
     * @param  Subscription  $subscription
     * @return Subscription
     */
    public function endTrial($subscription)
    {
        if (is_null($subscription->trial_ends_at)) {
            return $subscription;
        }

        $subscription->trial_ends_at = null;
        $subscription->save();

        return $subscription;
    }
}
