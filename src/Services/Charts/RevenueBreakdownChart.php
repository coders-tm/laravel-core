<?php

namespace Coderstm\Services\Charts;

use Coderstm\Services\Metrics\OrderMetrics;
use Illuminate\Http\Request;

class RevenueBreakdownChart
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get(): array
    {
        $orderMetrics = new OrderMetrics($this->request);

        return ['Subscription' => round($orderMetrics->getSubscriptionRevenue(), 2), 'Product' => round($orderMetrics->getNonSubscriptionRevenue(), 2)];
    }
}
