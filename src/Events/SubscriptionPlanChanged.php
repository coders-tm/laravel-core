<?php

namespace Coderstm\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Coderstm\Models\Subscription;
use Coderstm\Models\Subscription\Plan;

class SubscriptionPlanChanged
{
    use Dispatchable, SerializesModels;

    /**
     * The subscription instance.
     *
     * @var \Coderstm\Models\Subscription
     */
    public $subscription;

    /**
     * The old plan instance.
     *
     * @var \Coderstm\Models\Subscription\Plan
     */
    public $oldPlan;

    /**
     * The new plan instance.
     *
     * @var \Coderstm\Models\Subscription\Plan
     */
    public $newPlan;

    /**
     * Create a new event instance.
     *
     * @param  \Coderstm\Models\Subscription  $subscription
     * @param  \Coderstm\Models\Subscription\Plan  $oldPlan
     * @param  \Coderstm\Models\Subscription\Plan  $newPlan
     * @return void
     */
    public function __construct(Subscription $subscription, Plan $oldPlan, Plan $newPlan)
    {
        $this->subscription = $subscription;
        $this->oldPlan = $oldPlan;
        $this->newPlan = $newPlan;
    }

    /**
     * Determine if the plan's price has changed.
     *
     * @return bool
     */
    public function hasPriceChanged()
    {
        return $this->oldPlan->price != $this->newPlan->price;
    }

    /**
     * Determine if the plan's interval has changed.
     *
     * @return bool
     */
    public function hasIntervalChanged()
    {
        return $this->oldPlan->interval != $this->newPlan->interval ||
            $this->oldPlan->interval_count != $this->newPlan->interval_count;
    }

    /**
     * Determine if this is a downgrade (new price < old price).
     *
     * @return bool
     */
    public function isDowngrade()
    {
        return $this->newPlan->price < $this->oldPlan->price;
    }

    /**
     * Determine if this is an upgrade (new price > old price).
     *
     * @return bool
     */
    public function isUpgrade()
    {
        return $this->newPlan->price > $this->oldPlan->price;
    }
}
