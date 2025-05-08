<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Coderstm\Services\Period;
use Coderstm\Contracts\SubscriptionStatus;

trait ManagesSubscriptionPeriod
{
    /**
     * Force the trial to end immediately.
     *
     * This method must be combined with swap, resume, etc.
     *
     * @return $this
     */
    public function skipTrial()
    {
        $this->trial_ends_at = null;

        return $this;
    }

    /**
     * Force the subscription's trial to end immediately.
     *
     * @return $this
     */
    public function endTrial()
    {
        if (is_null($this->trial_ends_at)) {
            return $this;
        }

        $this->trial_ends_at = null;

        $this->save();

        return $this;
    }

    /**
     * Extend an existing subscription's trial period.
     *
     * @param  \Carbon\CarbonInterface  $date
     * @return $this
     */
    public function extendTrial(CarbonInterface $date)
    {
        if (!$date->isFuture()) {
            throw new \InvalidArgumentException("Extending a subscription's trial requires a date in the future.");
        }

        $this->trial_ends_at = $date;

        $this->save();

        return $this;
    }

    /**
     * Cancel the subscription at the end of the billing period.
     *
     * @return $this
     */
    public function cancel()
    {
        // If the user was on trial, we will set the grace period to end when the trial
        // would have ended. Otherwise, we'll retrieve the end of the billing period
        // period and make that the end of the grace period for this current user.
        if ($this->onTrial()) {
            $this->ends_at = $this->trial_ends_at;
        } else {
            $this->ends_at = $this->expires_at;
        }

        $this->canceled_at = now();

        $this->save();

        return $this;
    }

    /**
     * Cancel the subscription at a specific moment in time.
     *
     * @param  \DateTimeInterface|null  $endsAt
     * @return $this
     */
    public function cancelAt(?\DateTimeInterface $endsAt)
    {
        if ($endsAt instanceof \DateTimeInterface) {
            $this->ends_at = $endsAt->getTimestamp();
        }

        $this->status = SubscriptionStatus::CANCELED;
        $this->canceled_at = now();

        $this->save();

        return $this;
    }

    /**
     * Cancel the subscription immediately without invoicing.
     *
     * @return $this
     */
    public function cancelNow()
    {
        $this->fill([
            'status' => SubscriptionStatus::CANCELED,
            'ends_at' => now(),
            'canceled_at' => now(),
        ])->save();

        return $this;
    }

    /**
     * Resume the canceled subscription.
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function resume()
    {
        if (!$this->onGracePeriod()) {
            throw new \LogicException('Unable to resume subscription that is not within grace period.');
        }

        $this->guardAgainstIncomplete();

        // Finally, we will remove the ending timestamp from the user's record in the
        // local database to indicate that the subscription is active again and is
        // no longer "canceled". Then we shall save this record in the database.
        $this->fill([
            'status' => SubscriptionStatus::ACTIVE,
            'ends_at' => null,
            'canceled_at' => null,
        ])->save();

        return $this;
    }

    /**
     * Set new subscription period.
     *
     * @param string $interval
     * @param int|null $count
     * @param Carbon|null $dateFrom
     *
     * @return $this
     */
    protected function setPeriod(string $interval = '', ?int $count = null, ?Carbon $dateFrom = null): self
    {
        if (empty($interval)) {
            $interval = $this->plan->interval->value;
        }

        if (empty($count)) {
            $count = $this->plan->interval_count;
        }

        $period = new Period($interval, $count, $dateFrom ?? Carbon::now());

        $this->fill([
            'ends_at' => $this->ends_at?->lt(now()) ? null :  $this->ends_at,
            'starts_at' => $period->getStartDate(),
            'expires_at' => $period->getEndDate(),
        ]);

        return $this;
    }

    /**
     * Specify the number of days of the trial.
     *
     * @param  int  $trialDays
     * @return $this
     */
    public function trialDays($trialDays)
    {
        $this->trial_ends_at = Carbon::now()->addDays($trialDays);

        return $this;
    }

    /**
     * Specify the ending date of the trial.
     *
     * @param  \Carbon\Carbon|\Carbon\CarbonInterface  $trialUntil
     * @return $this
     */
    public function trialUntil($trialUntil)
    {
        $this->trial_ends_at = $trialUntil;

        return $this;
    }

    /**
     * Get current date for period calculation.
     *
     * @return Carbon
     */
    protected function dateFrom()
    {
        return $this->starts_at ?? $this->created_at;
    }
}
