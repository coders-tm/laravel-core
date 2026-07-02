<?php

namespace Coderstm\Services\Charts;

use Coderstm\Services\Metrics\SubscriptionMetrics;
use Illuminate\Http\Request;

class MembersBreakdownChart
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get(): array
    {
        $subscriptionMetrics = new SubscriptionMetrics($this->request);

        return ['Active' => $subscriptionMetrics->getActiveCount(), 'On Trial' => $subscriptionMetrics->getTrialCount(), 'Grace Period' => $subscriptionMetrics->getGracePeriodCount(), 'Cancelled' => $subscriptionMetrics->getCancelledCount()];
    }
}
