<?php

namespace Coderstm\Actions\Subscription;

use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Events\SubscriptionInvoiceGenerated;
use Coderstm\Models\Subscription;
use Coderstm\Services\Resource;

class GenerateSubscriptionInvoice
{
    /**
     * Generate subscription invoice.
     *
     * @param Subscription $subscription
     * @param bool $start
     * @param bool $force
     * @return mixed
     */
    public function execute($subscription, bool $start = false, bool $force = false)
    {
        $upcomingInvoice = $subscription->upcomingInvoice($start);

        if (! $upcomingInvoice) {
            return null;
        }

        if ($subscription->onTrial() && ! $force) {
            return null;
        }

        $order = Coderstm::$orderModel::modifyOrCreate(new Resource($upcomingInvoice->toArray()));

        do_action('subscription.invoice_generated', $subscription, $order);

        if ($order->is_paid) {
            $subscription->status = SubscriptionStatus::ACTIVE;
        } else {
            $subscription->status = $start ? SubscriptionStatus::PENDING : SubscriptionStatus::ACTIVE;
        }

        $subscription->next_plan = null;
        $subscription->is_downgrade = false;

        $subscription->save();

        event(new SubscriptionInvoiceGenerated($subscription, $order));

        return $order;
    }
}
