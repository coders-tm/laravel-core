<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Services\Period;
use InvalidArgumentException;

trait ManagesSubscriptions
{
    public function newSubscription(string $type, $plan, array $metadata = [])
    {
        if (empty($plan)) {
            throw new InvalidArgumentException('Please provide a plan when new subscription.');
        }
        if ($plan instanceof \Illuminate\Support\Collection) {
            $plan = $plan->first();
        }
        if (! $plan instanceof Coderstm::$planModel && ! is_object($plan)) {
            $plan = Coderstm::$planModel::find($plan);
        }
        if (! $plan) {
            throw new InvalidArgumentException('Invalid plan provided. Please ensure the plan exists.');
        }
        $trial = new Period('day', $plan->trial_days, Carbon::now());
        $billingStartDate = $trial->getEndDate();
        $isTrial = $plan->trial_days > 0;
        $period = new Period($plan->interval->value, $plan->interval_count, $billingStartDate);

        return new Coderstm::$subscriptionModel(['type' => $type, 'plan_id' => $plan->getKey(), 'status' => $isTrial ? SubscriptionStatus::TRIALING : SubscriptionStatus::PENDING, 'trial_ends_at' => $isTrial ? $trial->getEndDate() : null, 'starts_at' => $isTrial ? now() : $period->getStartDate(), 'expires_at' => $isTrial ? $trial->getEndDate() : $period->getEndDate(), 'billing_interval' => $plan->interval->value, 'billing_interval_count' => $plan->interval_count, 'total_cycles' => $plan->contract_cycles, $this->getForeignKey() => $this->getKey()]);
    }

    public function onTrial($type = 'default', $plan = null)
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }
        $subscription = $this->subscription($type);
        if (! $subscription || ! $subscription->onTrial()) {
            return false;
        }

        return ! $plan || $subscription->hasPlan($plan);
    }

    public function onIntroPricing($type = 'default', $plan = null)
    {
        $subscription = $this->subscription($type);
        if (! $subscription || ! $subscription->coupon) {
            return false;
        }
        $coupon = $subscription->coupon;
        if (! $coupon->is_intro_pricing) {
            return false;
        }
        if (! $coupon->canApplyToPlan($subscription->plan_id)) {
            return false;
        }
        switch ($coupon->duration->value) {
            case 'once':
                return $subscription->invoices()->count() <= 1;
            case 'repeating':
                $monthsElapsed = $subscription->starts_at->diffInMonths(Carbon::now());

                return $monthsElapsed < $coupon->duration_in_months;
            case 'forever':
                return true;
            default:
                return false;
        }
    }

    public function hasExpiredTrial($type = 'default', $plan = null)
    {
        if (func_num_args() === 0 && $this->hasExpiredGenericTrial()) {
            return true;
        }
        $subscription = $this->subscription($type);
        if (! $subscription || ! $subscription->hasExpiredTrial()) {
            return false;
        }

        return ! $plan || $subscription->hasPlan($plan);
    }

    public function onGenericTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function scopeOnGenericTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', Carbon::now());
    }

    public function hasExpiredGenericTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function scopeHasExpiredGenericTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', Carbon::now());
    }

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

    public function subscribed($type = 'default', $plan = null)
    {
        $subscription = $this->subscription($type);
        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        return ! $plan || $subscription->hasPlan($plan);
    }

    public function subscription($type = 'default')
    {
        return $this->subscriptions->where('type', $type)->first();
    }

    public function subscriptions()
    {
        return $this->hasMany(Coderstm::$subscriptionModel, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    public function hasIncompletePayment($type = 'default')
    {
        if ($subscription = $this->subscription($type)) {
            return $subscription->hasIncompletePayment();
        }

        return false;
    }
}
