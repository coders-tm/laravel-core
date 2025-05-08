<?php

namespace Coderstm\Traits\Subscription;

use Coderstm\Models\Log;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Events\SubscriptionPlanChanged;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ManagesSubscriptionPlan
{
    /**
     * Determine if the subscription has a specific plan.
     *
     * @param  string  $plan
     * @return bool
     */
    public function hasPlan($plan)
    {
        return $this->plan_id === $plan;
    }

    /**
     * Get plan relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get next plan relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nextPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'next_plan');
    }

    /**
     * Determine if the subscription has a next plan.
     *
     * @return bool
     */
    public function hasNexPlan()
    {
        return !is_null($this->next_plan);
    }

    /**
     * Cancel the downgrade plan.
     *
     * @return $this
     */
    public function cancelDowngrade()
    {
        if (!$this->hasNexPlan()) {
            return $this;
        }

        $newPlan = $this->plan;
        $oldPlan = $this->nextPlan;

        $this->update([
            'next_plan' => null,
            'is_downgrade' => false,
        ]);

        event(new SubscriptionPlanChanged($this, $oldPlan, $newPlan));

        return $this;
    }

    /**
     * Get the logs for plan cancellations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function planCanceled()
    {
        return $this->morphOne(Log::class, 'logable')
            ->where('type', 'plan-canceled')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Change subscription to a new plan.
     *
     * @param string|int $planId The plan ID to switch to
     * @param bool $invoiceNow Whether to generate charges for the new plan
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function swap($planId, $invoiceNow = true): self
    {
        if (empty($planId)) {
            throw new \InvalidArgumentException('Please provide a plan when swapping.');
        }

        $this->guardAgainstIncomplete();

        $oldPlan = $this->plan;
        $newPlan = Plan::findOrFail($planId);

        // Prevent swapping to the same plan
        if ($oldPlan->id == $newPlan->id) {
            throw new \InvalidArgumentException("Cannot swap to the same pla ({$oldPlan->name}).");
        }

        // Set new period based on the new plan
        $this->setPeriod($newPlan->interval->value, $newPlan->interval_count);

        // Attach new plan to subscription
        $this->plan()->associate($newPlan);

        $this->fill([
            'ends_at' => null
        ])->save();

        $this->syncUsages();

        if ($invoiceNow) {
            $this->generateInvoice(true);
        }

        // Fire the plan changed event
        event(new SubscriptionPlanChanged($this, $oldPlan, $newPlan));

        return $this;
    }

    /**
     * Assert that subscription can be renewed.
     *
     * @throws \LogicException
     */
    public function assertRenewable()
    {
        if ($this->ended()) {
            throw new \LogicException('Unable to renew canceled ended subscription.');
        }

        if ($this->onGracePeriod()) {
            throw new \LogicException('Unable to renew subscription that is not within grace period.');
        }
    }

    /**
     * Assert that subscription can be charged.
     *
     * @throws \LogicException
     */
    public function assertChargeable()
    {
        if ($this->pastDue() || $this->hasIncompletePayment()) {
            return;
        }

        throw new \LogicException('Unable to charge subscription that is not past due.');
    }

    /**
     * Renew subscription period.
     *
     * @throws \LogicException
     *
     * @return $this
     */
    public function renew(): self
    {
        if ($this->ended()) {
            throw new \LogicException('Unable to renew canceled ended subscription.');
        }

        // Clear usages data
        $this->usages()->delete();

        $this->detachActions();

        if ($this->nextPlan) {
            $this->plan()->associate($this->nextPlan);
        }

        // Renew period
        $this->setPeriod()->save();

        $this->generateInvoice();

        return $this;
    }
}
