<?php

namespace Coderstm\Actions\Subscription;

use Carbon\Carbon;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Events\SubscriptionExpired;
use Coderstm\Events\SubscriptionInvoiced;
use Coderstm\Models\PaymentMethod;
use Coderstm\Models\Subscription;
use Coderstm\Notifications\SubscriptionExpiredNotification;
use Coderstm\Services\Period;

class RenewSubscription
{
    /**
     * Renew the subscription.
     *
     * @param Subscription $subscription
     * @param bool $charge
     * @return Subscription
     */
    public function execute($subscription, bool $charge = true)
    {
        if (! $charge) {
            return $this->executeNoCharge($subscription);
        }

        $subscription->assertRenewable();

        if ($subscription->total_cycles && $subscription->current_cycle >= $subscription->total_cycles) {
            throw new \LogicException('Contract has reached its total cycles limit.');
        }

        $subscription->detachActions();

        if ($subscription->nextPlan) {
            $subscription->plan()->associate($subscription->nextPlan);

            $subscription->syncFeaturesFromPlan();

            $subscription->billing_interval = $subscription->nextPlan->interval->value;
            $subscription->billing_interval_count = $subscription->nextPlan->interval_count;
            $subscription->total_cycles = $subscription->nextPlan->contract_cycles;
            $subscription->current_cycle = 0;
            $subscription->next_plan = null;
            $subscription->is_downgrade = false;
        }

        $subscription->current_cycle = ($subscription->current_cycle ?? 0) + 1;

        $renewalInterval = $subscription->getBillingInterval();
        $renewalIntervalCount = $subscription->getBillingIntervalCount();

        $startDate = $subscription->expires_at ?? Carbon::now();
        $period = new Period($renewalInterval, $renewalIntervalCount, $startDate);

        $newExpiresAt = $period->getEndDate();
        if ($subscription->isContract()) {
            $contractPeriod = new Period(
                $subscription->plan->interval->value,
                $subscription->plan->interval_count,
                $subscription->created_at ?? $subscription->starts_at
            );
            $contractEndDate = $contractPeriod->getEndDate();

            if ($newExpiresAt->gt($contractEndDate)) {
                $newExpiresAt = $contractEndDate;
            }
        }

        $gracePeriodDays = $subscription->plan->grace_period_days ?? config('coderstm.subscription.grace_period_days', 0);
        $graceEndsAt = $gracePeriodDays > 0 ? Carbon::now()->addDays($gracePeriodDays) : null;

        $subscription->fill([
            'starts_at' => $period->getStartDate(),
            'expires_at' => $newExpiresAt,
            'ends_at' => $graceEndsAt,
            'trial_ends_at' => null,
        ])->save();

        $subscription->resetUsagesForRenewal();

        if ($subscription->credit_resets_at) {
            $subscription->advanceCreditResetsAt()->save();
        }

        $invoice = app(GenerateSubscriptionInvoice::class)->execute($subscription);

        $isPaid = $invoice && $invoice->is_paid;

        if (! $isPaid && $invoice && (float) $invoice->grand_total > 0) {
            event(new SubscriptionInvoiced($subscription, $invoice));

            $isPaid = $invoice->fresh()->is_paid;
        }

        if (! $isPaid && $invoice && config('coderstm.wallet.auto_charge_on_renewal', true) && $subscription->user) {
            try {
                if ($subscription->user->hasWalletBalance((float) $invoice->grand_total)) {
                    $this->chargeFromWallet($subscription, $invoice);
                    $isPaid = true;

                    $subscription->fill([
                        'status' => SubscriptionStatus::ACTIVE,
                        'ends_at' => null,
                    ])->save();
                } else {
                    throw new \Exception('Insufficient wallet balance.');
                }
            } catch (\Throwable $e) {
                logger()->error('Failed to charge wallet during subscription renewal', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $isPaid && ! $graceEndsAt && $invoice && (float) $invoice->grand_total > 0) {
            $subscription->update([
                'status' => SubscriptionStatus::EXPIRED,
                'ends_at' => null,
            ]);

            $subscription->attachAction('expired-notification');

            try {
                $subscription->user->notify(new SubscriptionExpiredNotification($subscription));
            } catch (\Throwable $e) {
                logger()->error('Failed to send subscription expired notification', ['error' => $e->getMessage()]);
            }

            try {
                admin_notify(new \Coderstm\Notifications\Admins\SubscriptionExpiredNotification($subscription));
            } catch (\Throwable $e) {
                logger()->error('Failed to send admin subscription expired notification', ['error' => $e->getMessage()]);
            }

            $subscription->logs()->create([
                'type' => 'expired-notification',
                'message' => 'Notification for expired subscriptions has been successfully sent.',
            ]);

            event(new SubscriptionExpired($subscription));
        }

        if ($subscription->total_cycles && $subscription->current_cycle >= $subscription->total_cycles) {
            app(CancelSubscription::class)->cancelNow($subscription);
        }

        return $subscription;
    }

    /**
     * Renew subscription without charge.
     *
     * @param Subscription $subscription
     * @return Subscription
     */
    protected function executeNoCharge($subscription)
    {
        if ($subscription->total_cycles && $subscription->current_cycle >= $subscription->total_cycles) {
            throw new \LogicException('Contract has reached its total cycles limit.');
        }

        $startDate = $subscription->expires_at ?? Carbon::now();
        $period = new Period(
            $subscription->getBillingInterval(),
            $subscription->getBillingIntervalCount(),
            $startDate
        );

        $isExpired = $subscription->expired();

        $subscription->fill([
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => $period->getEndDate(),
            'ends_at' => null,
            'trial_ends_at' => null,
            'canceled_at' => null,
        ])->save();

        if ($isExpired) {
            $subscription->resetUsagesForRenewal();
            $subscription->advanceCreditResetsAt()->save();
        }

        return $subscription;
    }

    /**
     * Charge subscription fee from wallet.
     *
     * @param Subscription $subscription
     * @param mixed $invoice
     * @return void
     */
    protected function chargeFromWallet($subscription, $invoice): void
    {
        $amount = (float) $invoice->grand_total;

        $transaction = $subscription->user->debitWallet(
            amount: $amount,
            source: 'subscription_renewal',
            description: "Subscription renewal - {$subscription->plan->label}",
            transactionable: $subscription,
            metadata: [
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'invoice_id' => $invoice->id,
                'cycle' => $subscription->current_cycle,
            ]
        );

        $walletPaymentMethod = PaymentMethod::where('provider', PaymentMethod::WALLET)->first();

        if ($walletPaymentMethod) {
            $invoice->markAsPaid($walletPaymentMethod->id, [
                'id' => $transaction->id,
                'amount' => $amount,
                'status' => 'succeeded',
                'note' => 'Paid from wallet balance',
                'wallet_transaction_id' => $transaction->id,
            ]);
        }
    }
}
