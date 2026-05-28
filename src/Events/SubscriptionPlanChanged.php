<?php

namespace Coderstm\Events;

use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPlanChanged
{
    use Dispatchable, SerializesModels;

    public $subscription;

    public $oldPlan;

    public $newPlan;

    public function __construct(Subscription $subscription, ?Plan $oldPlan, Plan $newPlan)
    {
        $this->subscription = $subscription;
        $this->oldPlan = $oldPlan;
        $this->newPlan = $newPlan;
    }

    public function hasPriceChanged()
    {
        return $this->oldPlan && $this->oldPlan->price != $this->newPlan->price;
    }

    public function hasIntervalChanged()
    {
        return $this->oldPlan && ($this->oldPlan->interval != $this->newPlan->interval || $this->oldPlan->interval_count != $this->newPlan->interval_count);
    }

    public function isDowngrade()
    {
        return $this->oldPlan && $this->newPlan->price < $this->oldPlan->price;
    }

    public function isUpgrade()
    {
        return $this->oldPlan && $this->newPlan->price > $this->oldPlan->price;
    }
}
