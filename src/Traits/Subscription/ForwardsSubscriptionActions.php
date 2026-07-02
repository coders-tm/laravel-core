<?php

namespace Coderstm\Traits\Subscription;

use Carbon\CarbonInterface;
use Coderstm\Actions\Subscription\CancelSubscription;
use Coderstm\Actions\Subscription\CancelSubscriptionDowngrade;
use Coderstm\Actions\Subscription\ExtendSubscriptionTrial;
use Coderstm\Actions\Subscription\FreezeSubscription;
use Coderstm\Actions\Subscription\GenerateSubscriptionInvoice;
use Coderstm\Actions\Subscription\ProcessSubscriptionPayment;
use Coderstm\Actions\Subscription\RenewSubscription;
use Coderstm\Actions\Subscription\ResumeSubscription;
use Coderstm\Actions\Subscription\SwapSubscriptionPlan;
use Coderstm\Actions\Subscription\UnfreezeSubscription;
use Coderstm\Coderstm;

trait ForwardsSubscriptionActions
{
    public function swap($planId, $billing = 'monthly', bool $invoiceNow = true): self
    {
        return app(SwapSubscriptionPlan::class)->execute($this, $planId, $billing, $invoiceNow);
    }

    public function forceSwap($plan, $billing = 'monthly')
    {
        return app(SwapSubscriptionPlan::class)->execute($this, $plan, $billing, true, true);
    }

    public function renew(bool $charge = true): self
    {
        return app(RenewSubscription::class)->execute($this, $charge);
    }

    public function cancelDowngrade(): self
    {
        return app(CancelSubscriptionDowngrade::class)->execute($this);
    }

    public function freeze($releaseAt = null, $reason = null, $fee = null)
    {
        return app(FreezeSubscription::class)->execute($this, $releaseAt, $reason, $fee);
    }

    public function unfreeze()
    {
        return app(UnfreezeSubscription::class)->execute($this);
    }

    public function cancel(): self
    {
        return app(CancelSubscription::class)->execute($this);
    }

    public function cancelAt(?\DateTimeInterface $endsAt)
    {
        return app(CancelSubscription::class)->cancelAt($this, $endsAt);
    }

    public function cancelNow(): self
    {
        return app(CancelSubscription::class)->cancelNow($this);
    }

    public function resume(): self
    {
        return app(ResumeSubscription::class)->execute($this);
    }

    public function extendTrial(CarbonInterface $date)
    {
        return app(ExtendSubscriptionTrial::class)->extendTrial($this, $date);
    }

    public function trialDays(int $trialDays): self
    {
        return app(ExtendSubscriptionTrial::class)->trialDays($this, $trialDays);
    }

    public function trialUntil($trialUntil): self
    {
        return app(ExtendSubscriptionTrial::class)->trialUntil($this, $trialUntil);
    }

    public function endTrial(): self
    {
        return app(ExtendSubscriptionTrial::class)->endTrial($this);
    }

    public function skipTrial(): self
    {
        return app(ExtendSubscriptionTrial::class)->endTrial($this);
    }

    public function generateInvoice($start = false, $force = false)
    {
        return app(GenerateSubscriptionInvoice::class)->execute($this, $start, $force);
    }

    public function pay($paymentMethod, array $options = []): self
    {
        return app(ProcessSubscriptionPayment::class)->pay($this, $paymentMethod, $options);
    }

    public function paymentConfirmation($order = null): self
    {
        return app(ProcessSubscriptionPayment::class)->paymentConfirmation($this, $order);
    }

    public function paymentFailed($order = null): self
    {
        return app(ProcessSubscriptionPayment::class)->paymentFailed($this, $order);
    }

    public function cancelOpenInvoices(): self
    {
        $openInvoices = $this->invoices()->where('status', Coderstm::$orderModel::STATUS_OPEN);
        foreach ($openInvoices->cursor() as $order) {
            $order->markAsCancelled();
        }

        return $this;
    }
}
