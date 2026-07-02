<?php

namespace Coderstm\Actions\Subscription;

class CancelSubscriptionDowngrade
{
    public function execute($subscription)
    {
        if (! $subscription->hasNexPlan()) {
            return $subscription;
        }
        $subscription->update(['next_plan' => null, 'is_downgrade' => false]);

        return $subscription;
    }
}
