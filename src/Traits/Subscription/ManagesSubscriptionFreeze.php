<?php

namespace Coderstm\Traits\Subscription;

use Carbon\Carbon;
use Coderstm\Contracts\SubscriptionStatus;

trait ManagesSubscriptionFreeze
{
    public function onFreeze(): bool
    {
        return ! is_null($this->frozen_at) && $this->status === SubscriptionStatus::PAUSED && (is_null($this->release_at) || $this->release_at->isFuture());
    }

    public function canFreeze(int $days = 0): bool
    {
        if (! $this->plan->allowsFreeze()) {
            return false;
        }
        if ($this->onFreeze()) {
            return false;
        }
        if ($this->canceled() || $this->expired()) {
            return false;
        }

        return true;
    }

    public function freeze(?Carbon $releaseAt = null, ?string $reason = null, ?float $fee = null): self
    {
        $freezeDays = $releaseAt ? now()->diffInDays($releaseAt) : 0;
        if (! $this->canFreeze($freezeDays)) {
            throw new \LogicException('Subscription cannot be frozen at this time.');
        }
        $freezeFee = $fee ?? $this->plan->getFreezeFee();
        $this->fill(['status' => SubscriptionStatus::PAUSED, 'frozen_at' => now(), 'release_at' => $releaseAt])->save();
        if ($freezeFee > 0) {
            $this->generateFreezeInvoice($freezeFee, $releaseAt);
        }
        $logMessage = 'Subscription frozen';
        if ($reason) {
            $logMessage .= ": {$reason}";
        }
        if ($releaseAt) {
            $logMessage .= " (until {$releaseAt->format('Y-m-d')})";
        }
        $this->logs()->create(['type' => \Coderstm\Enum\LogType::UPDATED, 'message' => $logMessage]);

        return $this;
    }

    public function unfreeze(): self
    {
        if (! $this->onFreeze()) {
            throw new \LogicException('Subscription is not currently frozen.');
        }
        $freezeDuration = $this->frozen_at->diffInDays(now());
        if ($this->isContract() && $this->total_cycles) {
            $this->extendContractForFreeze($freezeDuration);
        }
        $this->fill(['status' => SubscriptionStatus::ACTIVE, 'frozen_at' => null, 'release_at' => null])->save();
        $this->logs()->create(['type' => 'unfreeze', 'message' => "Subscription unfrozen after {$freezeDuration} days"]);

        return $this;
    }

    protected function extendContractForFreeze(int $freezeDays): void
    {
        if (! $this->expires_at) {
            return;
        }
        $this->expires_at = $this->expires_at->addDays($freezeDays);
    }

    protected function generateFreezeInvoice(float $fee, ?Carbon $releaseAt = null)
    {
        $period = $releaseAt ? now()->diffInDays($releaseAt).' days' : 'indefinite';

        return $this->invoices()->create(['user_id' => $this->user_id, 'status' => 'open', 'description' => "Subscription freeze fee ({$period})", 'sub_total' => $fee, 'tax' => 0, 'total' => $fee, 'grand_total' => $fee, 'lines' => [['description' => "Freeze Fee - {$this->plan->label}", 'quantity' => 1, 'unit_amount' => $fee, 'amount' => $fee]]]);
    }

    public function scopeFrozen($query)
    {
        return $query->where('status', SubscriptionStatus::PAUSED)->whereNotNull('frozen_at');
    }

    public function scopeDueForUnfreeze($query)
    {
        return $query->frozen()->whereNotNull('release_at')->where('release_at', '<=', now());
    }
}
