<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Services\Period;

trait ManagesSubscriptionPeriod
{
    protected $hasCustomDates = false;

    protected function anchorActivationFromInvoice(): bool
    {
        return (bool) config('coderstm.subscription.anchor_from_invoice', false) && $this->expires_at && $this->expires_at->isFuture();
    }

    public function skipTrial(): self
    {
        $this->trial_ends_at = null;

        return $this;
    }

    public function endTrial(): self
    {
        if (is_null($this->trial_ends_at)) {
            return $this;
        }
        $this->trial_ends_at = null;
        $this->save();

        return $this;
    }

    public function extendTrial(CarbonInterface $date): self
    {
        if (! $date->isFuture()) {
            throw new \InvalidArgumentException("Extending a subscription's trial requires a date in the future.");
        }
        $this->trial_ends_at = $date;
        $this->save();

        return $this;
    }

    public function cancel(): self
    {
        if ($this->onTrial()) {
            $this->expires_at = $this->trial_ends_at;
        }
        $this->canceled_at = now();
        $this->save();

        return $this;
    }

    public function cancelAt(?\DateTimeInterface $endsAt): self
    {
        if ($endsAt instanceof \DateTimeInterface) {
            $this->expires_at = $endsAt->getTimestamp();
        }
        $this->status = SubscriptionStatus::CANCELED;
        $this->canceled_at = now();
        $this->save();

        return $this;
    }

    public function cancelNow(): self
    {
        $this->fill(['status' => SubscriptionStatus::CANCELED, 'expires_at' => now(), 'canceled_at' => now()])->save();

        return $this;
    }

    public function resume(): self
    {
        if (! $this->canceledOnGracePeriod()) {
            throw new \LogicException('Unable to resume subscription that is not within grace period.');
        }
        $this->guardAgainstIncomplete();
        $period = new Period($this->plan->interval->value, $this->plan->interval_count, $this->starts_at ?? Carbon::now());
        $this->fill(['status' => SubscriptionStatus::ACTIVE, 'expires_at' => $period->getEndDate(), 'canceled_at' => null])->save();

        return $this;
    }

    protected function setPeriod(string $interval = '', ?int $count = null, ?Carbon $dateFrom = null): self
    {
        if ($this->hasCustomDates) {
            return $this;
        }
        if ($this->anchorActivationFromInvoice()) {
            $dateFrom = $this->starts_at?->copy() ?? $dateFrom;
        }
        if (empty($interval)) {
            $interval = $this->plan->interval->value;
        }
        if (empty($count)) {
            $count = $this->plan->interval_count;
        }
        $period = new Period($interval, $count, $dateFrom ?? Carbon::now());
        $this->fill(['starts_at' => $period->getStartDate(), 'expires_at' => $period->getEndDate(), 'billing_interval' => $this->plan->interval->value, 'billing_interval_count' => $this->plan->interval_count]);
        if ($this->plan->isContract() && is_null($this->total_cycles)) {
            $this->total_cycles = $this->plan->contract_cycles;
            $this->current_cycle = 0;
        }

        return $this;
    }

    public function contractCycles(?int $cycles): self
    {
        $this->total_cycles = $cycles;
        $this->current_cycle = 0;

        return $this;
    }

    public function contractComplete(): bool
    {
        if (! $this->total_cycles) {
            return false;
        }

        return $this->current_cycle >= $this->total_cycles;
    }

    protected function setPeriodFromDate(Carbon $dateFrom): self
    {
        return $this->setPeriod('', null, $dateFrom);
    }

    public function trialDays(int $trialDays): self
    {
        $this->trial_ends_at = Carbon::now()->addDays($trialDays);
        $this->status = SubscriptionStatus::TRIALING;

        return $this;
    }

    public function trialUntil($trialUntil): self
    {
        $this->trial_ends_at = $trialUntil;
        $this->status = SubscriptionStatus::TRIALING;

        return $this;
    }

    protected function dateFrom(): Carbon
    {
        return $this->starts_at ?? $this->created_at;
    }

    public function getBillingInterval(): string
    {
        if ($this->billing_interval) {
            return is_string($this->billing_interval) ? $this->billing_interval : $this->billing_interval->value;
        }

        return $this->plan->interval->value;
    }

    public function getBillingIntervalCount(): int
    {
        return $this->billing_interval_count ?? $this->plan->interval_count;
    }

    public function isContract(): bool
    {
        return $this->plan && $this->plan->isContract();
    }

    public function setStartsAt($date): self
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        } elseif ($date instanceof \DateTimeInterface && ! $date instanceof Carbon) {
            $date = Carbon::instance($date);
        }
        $this->starts_at = $date;
        $this->hasCustomDates = true;

        return $this;
    }

    public function setExpiresAt($date): self
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        } elseif ($date instanceof \DateTimeInterface && ! $date instanceof Carbon) {
            $date = Carbon::instance($date);
        }
        $this->expires_at = $date;
        $this->hasCustomDates = true;

        return $this;
    }
}
