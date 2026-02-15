<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Exceptions\SubscriptionUpdateFailure;

trait ManageSubscriptionStatus
{
    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->canceledOnGracePeriod() || $this->onGracePeriod();
    }

    public function pending(): bool
    {
        return $this->status === SubscriptionStatus::PENDING;
    }

    public function scopePending($query)
    {
        $query->where('status', SubscriptionStatus::PENDING);
    }

    public function incomplete(): bool
    {
        return $this->status === SubscriptionStatus::INCOMPLETE;
    }

    public function scopeIncomplete($query)
    {
        $query->where('status', SubscriptionStatus::INCOMPLETE);
    }

    public function expired(): bool
    {
        return $this->status === SubscriptionStatus::EXPIRED;
    }

    public function scopeExpired($query)
    {
        $query->where('status', SubscriptionStatus::EXPIRED);
    }

    public function active(): bool
    {
        return ! $this->ended() && $this->status === SubscriptionStatus::ACTIVE;
    }

    public function scopeActive($query)
    {
        $query->where(function ($query) {
            $query->whereNull('canceled_at')->orWhere(function ($query) {
                $query->canceledOnGracePeriod();
            });
        })->where('status', SubscriptionStatus::ACTIVE);
    }

    public function scopeFree($query)
    {
        $query->whereHas('plan', function ($query) {
            $query->where('price', 0);
        });
    }

    public function recurring(): bool
    {
        return ! $this->onTrial() && ! $this->canceled();
    }

    public function scopeRecurring($query)
    {
        $query->notOnTrial()->notCanceled();
    }

    public function canceled(): bool
    {
        return ! is_null($this->canceled_at);
    }

    public function scopeCanceled($query)
    {
        $query->whereNotNull('canceled_at');
    }

    public function scopeNotCanceled($query)
    {
        $query->whereNull('canceled_at');
    }

    public function ended()
    {
        return $this->canceled() && ! $this->canceledOnGracePeriod();
    }

    public function scopeEnded($query)
    {
        $query->canceled()->canceledNotOnGracePeriod();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasDowngrade(): bool
    {
        return $this->is_downgrade && $this->next_plan;
    }

    public function scopeOnTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', Carbon::now());
    }

    public function hasExpiredTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function scopeExpiredTrial($query)
    {
        $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', Carbon::now());
    }

    public function scopeNotOnTrial($query)
    {
        $query->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', Carbon::now());
    }

    public function canceledOnGracePeriod(): bool
    {
        return $this->canceled_at && $this->expires_at && $this->expires_at->isFuture();
    }

    public function scopeCanceledOnGracePeriod($query)
    {
        $query->whereNotNull('canceled_at')->whereNotNull('expires_at')->where('expires_at', '>', Carbon::now());
    }

    public function scopeCanceledNotOnGracePeriod($query)
    {
        $query->whereNotNull('canceled_at')->whereNotNull('expires_at')->where('expires_at', '<=', Carbon::now());
    }

    public function onGracePeriod(): bool
    {
        if ($this->status !== SubscriptionStatus::ACTIVE) {
            return false;
        }

        return $this->ends_at?->isFuture() ?? false;
    }

    public function notOnGracePeriod(): bool
    {
        return ! $this->onGracePeriod();
    }

    public function scopeOnGracePeriod($query)
    {
        $query->where('status', SubscriptionStatus::ACTIVE)->whereNotNull('ends_at')->where('ends_at', '>', Carbon::now());
    }

    public function scopeNotOnGracePeriod($query)
    {
        $query->where(function ($q) {
            $q->where('status', '<>', SubscriptionStatus::ACTIVE)->orWhereNull('ends_at')->orWhere('ends_at', '<=', Carbon::now());
        });
    }

    public function hasIncompletePayment(): bool
    {
        return $this->expired() || $this->incomplete() || $this->pending();
    }

    public function guardAgainstIncomplete()
    {
        if ($this->incomplete()) {
            throw SubscriptionUpdateFailure::incompleteSubscription($this);
        }
    }

    public function toResponse(array $extends = []): array
    {
        $status = ['id' => $this->id, 'status' => $this->status, 'active' => $this->active(), 'canceled' => $this->canceled(), 'ended' => $this->ended(), 'expired' => $this->expired(), 'downgrade' => $this->hasDowngrade(), 'on_grace_period' => $this->onGracePeriod(), 'canceled_on_grace_period' => $this->canceledOnGracePeriod(), 'has_incomplete_payment' => $this->hasIncompletePayment(), 'has_due' => $this->onGracePeriod() || $this->expired() || $this->hasIncompletePayment(), 'on_trial' => $this->onTrial(), 'is_valid' => $this->valid() ?? false, 'type' => $this->type, 'is_downgrade' => $this->is_downgrade, 'next_plan' => $this->next_plan, 'trial_ends_at' => $this->serializeDate($this->trial_ends_at), 'expires_at' => $this->serializeDate($this->expires_at), 'ends_at' => $this->serializeDate($this->ends_at), 'starts_at' => $this->serializeDate($this->starts_at), 'canceled_at' => $this->serializeDate($this->canceled_at), 'frozen_at' => $this->serializeDate($this->frozen_at), 'release_at' => $this->serializeDate($this->release_at), 'provider' => $this->provider, 'metadata' => $this->metadata ?? [], 'billing_interval' => $this->billing_interval, 'billing_interval_count' => $this->billing_interval_count, 'total_cycles' => $this->total_cycles, 'current_cycle' => $this->current_cycle, 'created_at' => $this->serializeDate($this->created_at), 'updated_at' => $this->serializeDate($this->updated_at), 'invoice' => null];
        try {
            $upcomingInvoice = $this->upcomingInvoice();
        } catch (\Throwable $e) {
            $upcomingInvoice = null;
        }
        if ($this->onGracePeriod() || $this->expired() || $this->hasIncompletePayment()) {
            $invoice = $this->latestInvoice ?? $upcomingInvoice;
            $amount = $invoice?->total();
            $status['invoice'] = ['amount' => $amount, 'key' => $invoice?->key];
        } elseif ($upcomingInvoice) {
            $status['invoice'] = ['amount' => $upcomingInvoice->total(), 'date' => $upcomingInvoice->due_date->format('d M, Y')];
        }
        if (in_array('plan', $extends)) {
            $status['plan'] = $this->plan;
        }
        if (in_array('user', $extends)) {
            $status['user'] = $this->user;
        }
        if (in_array('next_plan', $extends) && $this->hasDowngrade()) {
            $status['next_plan'] = $this->next_plan;
        }
        if (in_array('usages', $extends)) {
            $status['usages'] = $this->usagesToArray();
        }

        return $status;
    }
}
