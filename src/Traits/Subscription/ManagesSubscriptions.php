<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Services\Period;
use InvalidArgumentException;

trait ManagesSubscriptions
{
    /**
     * Begin creating a new subscription.
     *
     * @param  string  $type
     * @param  string $plan
     * @return \Coderstm\Models\Subscription
     */
    public function newSubscription(string $type, $plan)
    {
        if (empty($plan)) {
            throw new InvalidArgumentException('Please provide a plan when new subscription.');
        }

        $plan = Coderstm::$planModel::find($plan);

        $trial = new Period(
            'day',
            $plan->trial_days,
            Carbon::now()
        );

        $period = new Period(
            $plan->interval->value,
            $plan->interval_count,
            $trial->getEndDate()
        );

        return new Coderstm::$subscriptionModel([
            'type' => $type,
            'plan_id' => $plan->getKey(),
            'trial_ends_at' => $plan->trial_days ? $trial->getEndDate() : null,
            'starts_at' => $period->getStartDate(),
            'expires_at' => $period->getEndDate(),
            $this->getForeignKey() => $this->getKey(),
        ]);
    }

    /**
     * Determine if the Stripe model is on trial.
     *
     * @param  string  $type
     * @param  string|null  $plan
     * @return bool
     */
    public function onTrial($type = 'default', $plan = null)
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($type);

        if (!$subscription || !$subscription->onTrial()) {
            return false;
        }

        return !$plan || $subscription->hasPlan($plan);
    }

    /**
     * Determine if the Stripe model's trial has ended.
     *
     * @param  string  $type
     * @param  string|null  $plan
     * @return bool
     */
    public function hasExpiredTrial($type = 'default', $plan = null)
    {
        if (func_num_args() === 0 && $this->hasExpiredGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($type);

        if (!$subscription || !$subscription->hasExpiredTrial()) {
            return false;
        }

        return !$plan || $subscription->hasPlan($plan);
    }

    /**
     * Determine if the Stripe model is on a "generic" trial at the model level.
     *
     * @return bool
     */
    public function onGenericTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Filter the given query for generic trials.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeOnGenericTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', Carbon::now());
    }

    /**
     * Determine if the Stripe model's "generic" trial at the model level has expired.
     *
     * @return bool
     */
    public function hasExpiredGenericTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Filter the given query for expired generic trials.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeHasExpiredGenericTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', Carbon::now());
    }

    /**
     * Get the ending date of the trial.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Carbon|null
     */
    public function trialEndsAt($type = 'default')
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return $this->trial_ends_at;
        }

        if ($subscription = $this->subscription($type)) {
            return $subscription->trial_ends_at;
        }

        return $this->trial_ends_at;
    }

    /**
     * Determine if the Stripe model has a given subscription.
     *
     * @param  string  $type
     * @param  string|null  $plan
     * @return bool
     */
    public function subscribed($type = 'default', $plan = null)
    {
        $subscription = $this->subscription($type);

        if (!$subscription || !$subscription->valid()) {
            return false;
        }

        return !$plan || $subscription->hasPlan($plan);
    }

    /**
     * Get a subscription instance by $type.
     *
     * @param  string  $type
     * @return \Coderstm\Models\Subscription|null
     */
    public function subscription($type = 'default')
    {
        return $this->subscriptions->where('type', $type)->first();
    }

    /**
     * Get all of the subscriptions for the Stripe model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(Coderstm::$subscriptionModel, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * Determine if the customer's subscription has an incomplete payment.
     *
     * @param  string  $type
     * @return bool
     */
    public function hasIncompletePayment($type = 'default')
    {
        if ($subscription = $this->subscription($type)) {
            return $subscription->hasIncompletePayment();
        }

        return false;
    }
}
