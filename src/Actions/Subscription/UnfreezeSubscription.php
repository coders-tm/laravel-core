<?php

namespace Coderstm\Actions\Subscription;

use Coderstm\Contracts\SubscriptionStatus;

class UnfreezeSubscription
{
    public function execute($subscription)
    {
        if (! $subscription->onFreeze()) {
            throw new \LogicException('Subscription is not currently frozen.');
        }
        $freezeDuration = $subscription->frozen_at->diffInDays(now());
        if ($subscription->isContract() && $subscription->total_cycles) {
            $this->extendContractForFreeze($subscription, $freezeDuration);
        }
        $subscription->fill(['status' => SubscriptionStatus::ACTIVE, 'frozen_at' => null, 'release_at' => null])->save();
        $subscription->logs()->create(['type' => 'unfreeze', 'message' => "Subscription unfrozen after {$freezeDuration} days"]);

        return $subscription;
    }

    protected function extendContractForFreeze($subscription, int $freezeDays): void
    {
        if (! $subscription->expires_at) {
            return;
        }
        $subscription->expires_at = $subscription->expires_at->addDays($freezeDays);
    }
}
