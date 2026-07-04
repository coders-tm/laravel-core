<?php

namespace Coderstm\Actions\Subscription;

use Coderstm\Models\Subscription;

class CancelSubscriptionDowngrade
{
    /**
     * Cancel subscription downgrade.
     *
     * @param  Subscription  $subscription
     * @return Subscription
     */
    public function execute($subscription)
    {
        if (! $subscription->hasNexPlan()) {
            return $subscription;
        }

        $subscription->update([
            'next_plan' => null,
            'is_downgrade' => false,
        ]);

        return $subscription;
    }
}
