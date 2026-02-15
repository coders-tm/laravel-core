<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Events\SubscriptionExpired;
use Coderstm\Events\SubscriptionPlanChanged;
use Coderstm\Notifications\SubscriptionExpiredNotification;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ManagesSubscriptionPlan
{
    use ManagesSubscriptionPeriod;

    public function hasPlan($plan)
    {
        return $this->plan_id === $plan;
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$planModel);
    }

    public function nextPlan(): BelongsTo
    {
        return $this->belongsTo(Coderstm::$planModel, 'next_plan');
    }

    public function hasNexPlan()
    {
        return ! is_null($this->next_plan);
    }

    public function cancelDowngrade(): self
    {
        if (! $this->hasNexPlan()) {
            return $this;
        }
        $this->update(['next_plan' => null, 'is_downgrade' => false]);

        return $this;
    }

    public function swap($planId, $invoiceNow = true): self
    {
        if (empty($planId)) {
            throw new \InvalidArgumentException('Please provide a plan when swapping.');
        }
        $this->guardAgainstIncomplete();
        $this->loadMissing(['plan']);
        $oldPlan = $this->plan;
        $newPlan = Coderstm::$planModel::findOrFail($planId);
        if ($oldPlan->id == $newPlan->id && $this->valid()) {
            throw new \InvalidArgumentException("Cannot swap to the same plan ({$oldPlan->label}).");
        }
        $this->setPeriod($newPlan->interval->value, $newPlan->interval_count);
        $this->plan()->associate($newPlan);
        $this->fill(['canceled_at' => null, 'billing_interval' => $newPlan->interval->value, 'billing_interval_count' => $newPlan->interval_count, 'total_cycles' => $newPlan->contract_cycles, 'current_cycle' => 0])->save();
        $this->syncFeaturesFromPlan();
        if ($invoiceNow) {
            $this->cancelOpenInvoices();
            $this->generateInvoice(true);
        }
        event(new SubscriptionPlanChanged($this, $oldPlan, $newPlan));

        return $this;
    }

    public function forceSwap($planId, $invoiceNow = true): self
    {
        if (empty($planId)) {
            throw new \InvalidArgumentException('Please provide a plan when swapping.');
        }
        $oldPlan = $this->plan;
        $newPlan = Coderstm::$planModel::findOrFail($planId);
        $this->setPeriod($newPlan->interval->value, $newPlan->interval_count);
        $this->plan()->associate($newPlan);
        $this->fill(['canceled_at' => null, 'billing_interval' => $newPlan->interval->value, 'billing_interval_count' => $newPlan->interval_count, 'total_cycles' => $newPlan->contract_cycles, 'current_cycle' => 0])->save();
        $this->syncFeaturesFromPlan();
        if ($invoiceNow) {
            $this->cancelOpenInvoices();
            $this->generateInvoice(true);
        }
        event(new SubscriptionPlanChanged($this, $oldPlan, $newPlan));

        return $this;
    }

    public function assertRenewable()
    {
        if ($this->ended()) {
            throw new \LogicException('Unable to renew canceled ended subscription.');
        }
        if ($this->onGracePeriod()) {
            throw new \LogicException('Unable to renew subscription that is not within grace period.');
        }
    }

    public function assertChargeable()
    {
        if ($this->expired() || $this->hasIncompletePayment()) {
            return;
        }
        throw new \LogicException('Unable to charge subscription that is not expired.');
    }

    public function renew(): self
    {
        if ($this->ended()) {
            throw new \LogicException('Unable to renew canceled ended subscription.');
        }
        if ($this->total_cycles && $this->current_cycle >= $this->total_cycles) {
            throw new \LogicException('Contract has reached its total cycles limit.');
        }
        $this->detachActions();
        if ($this->nextPlan) {
            $this->plan()->associate($this->nextPlan);
            $this->syncFeaturesFromPlan();
            $this->billing_interval = $this->nextPlan->interval->value;
            $this->billing_interval_count = $this->nextPlan->interval_count;
            $this->total_cycles = $this->nextPlan->contract_cycles;
            $this->current_cycle = 0;
            $this->next_plan = null;
            $this->is_downgrade = false;
        } else {
            $this->resetUsagesForRenewal();
        }
        $this->current_cycle = ($this->current_cycle ?? 0) + 1;
        $renewalInterval = $this->getBillingInterval();
        $renewalIntervalCount = $this->getBillingIntervalCount();
        $startDate = $this->expires_at ?? Carbon::now();
        $period = new \Coderstm\Services\Period($renewalInterval, $renewalIntervalCount, $startDate);
        $newExpiresAt = $period->getEndDate();
        if ($this->isContract()) {
            $contractPeriod = new \Coderstm\Services\Period($this->plan->interval->value, $this->plan->interval_count, $this->created_at ?? $this->starts_at);
            $contractEndDate = $contractPeriod->getEndDate();
            if ($newExpiresAt->gt($contractEndDate)) {
                $newExpiresAt = $contractEndDate;
            }
        }
        $gracePeriodDays = $this->plan->grace_period_days ?? config('coderstm.subscription.grace_period_days', 0);
        $graceEndsAt = $gracePeriodDays > 0 ? Carbon::now()->addDays($gracePeriodDays) : null;
        $this->fill(['starts_at' => $period->getStartDate(), 'expires_at' => $newExpiresAt, 'ends_at' => $graceEndsAt, 'trial_ends_at' => null])->save();
        $invoice = $this->generateInvoice();
        if ($invoice && config('coderstm.wallet.auto_charge_on_renewal', true) && $this->user) {
            try {
                if ($this->user->hasWalletBalance((float) $invoice->grand_total)) {
                    $this->chargeFromWallet($invoice);
                    $this->fill(['status' => SubscriptionStatus::ACTIVE, 'ends_at' => null])->save();
                } else {
                    throw new \Exception('Insufficient wallet balance.');
                }
            } catch (\Throwable $e) {
                logger()->error('Failed to charge wallet during subscription renewal', ['subscription_id' => $this->id, 'user_id' => $this->user_id, 'error' => $e->getMessage()]);
                if (! $graceEndsAt) {
                    $this->update(['status' => SubscriptionStatus::EXPIRED, 'ends_at' => null]);
                    $this->attachAction('expired-notification');
                    try {
                        $this->user->notify(new SubscriptionExpiredNotification($this));
                    } catch (\Throwable $e) {
                        logger()->error('Failed to send subscription expired notification', ['error' => $e->getMessage()]);
                    }
                    try {
                        admin_notify(new \Coderstm\Notifications\Admins\SubscriptionExpiredNotification($this));
                    } catch (\Throwable $e) {
                        logger()->error('Failed to send admin subscription expired notification', ['error' => $e->getMessage()]);
                    }
                    $this->logs()->create(['type' => 'expired-notification', 'message' => 'Notification for expired subscriptions has been successfully sent (Wallet Failure).']);
                    event(new SubscriptionExpired($this));
                }
            }
        }
        if ($this->total_cycles && $this->current_cycle >= $this->total_cycles) {
            $this->cancelNow();
        }

        return $this;
    }

    protected function chargeFromWallet($invoice): void
    {
        $amount = (float) $invoice->grand_total;
        $transaction = $this->user->debitWallet(amount: $amount, source: 'subscription_renewal', description: "Subscription renewal - {$this->plan->label}", transactionable: $this, metadata: ['subscription_id' => $this->id, 'plan_id' => $this->plan_id, 'invoice_id' => $invoice->id, 'cycle' => $this->current_cycle]);
        $walletPaymentMethod = \Coderstm\Models\PaymentMethod::where('provider', \Coderstm\Models\PaymentMethod::WALLET)->first();
        if ($walletPaymentMethod) {
            $invoice->markAsPaid($walletPaymentMethod->id, ['id' => $transaction->id, 'amount' => $amount, 'status' => 'succeeded', 'note' => 'Paid from wallet balance', 'wallet_transaction_id' => $transaction->id]);
        }
    }
}
