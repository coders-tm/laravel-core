<?php

namespace Coderstm\Actions\Subscription;

use Coderstm\Coderstm;
use Coderstm\Events\SubscriptionPlanChanged;
use Coderstm\Models\Subscription;

class SwapSubscriptionPlan
{
    /**
     * Swap subscription plan.
     *
     * @param Subscription $subscription
     * @param mixed $planId
     * @param bool $invoiceNow
     * @param bool $force
     * @return Subscription
     */
    public function execute($subscription, $planId, $billing = 'monthly', bool $invoiceNow = true, bool $force = false)
    {
        if (is_bool($billing)) {
            $force = $invoiceNow;
            $invoiceNow = $billing;
            $billing = 'monthly';
        }

        if (empty($planId)) {
            throw new \InvalidArgumentException('Please provide a plan when swapping.');
        }

        if (! $force) {
            $subscription->guardAgainstIncomplete();
        }

        $subscription->loadMissing(['plan']);

        $oldPlan = $subscription->plan;
        $newPlan = Coderstm::$planModel::findOrFail($planId);

        if (! $force && $oldPlan && $oldPlan->id == $newPlan->id && $subscription->valid()) {
            throw new \InvalidArgumentException("Cannot swap to the same plan ({$oldPlan->label}).");
        }

        $subscription->plan()->associate($newPlan);

        $billingInterval = $billing === 'yearly' ? 'year' : $newPlan->interval->value;
        $billingIntervalCount = $billing === 'yearly' ? 1 : $newPlan->interval_count;

        $subscription->setPeriod($billingInterval, $billingIntervalCount, null, true);

        $subscription->fill([
            'canceled_at' => null,
            'billing_interval' => $billingInterval,
            'billing_interval_count' => $billingIntervalCount,
            'total_cycles' => $newPlan->contract_cycles,
            'current_cycle' => 0,
            'credit_resets_at' => null,
        ])->save();

        $subscription->syncFeaturesFromPlan();

        if ($invoiceNow) {
            $openInvoices = $subscription->invoices()->where('status', Coderstm::$orderModel::STATUS_OPEN);
            foreach ($openInvoices->cursor() as $order) {
                $order->markAsCancelled();
            }

            app(GenerateSubscriptionInvoice::class)->execute($subscription, true);
        }

        event(new SubscriptionPlanChanged($subscription, $oldPlan, $newPlan));

        return $subscription;
    }
}
