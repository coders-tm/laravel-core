<?php

namespace Coderstm\Services\Charts;

use Illuminate\Http\Request;

class ChartService
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRevenueChart(): array
    {
        return (new RevenueChart($this->request))->get();
    }

    public function getSubscriptionChart(): array
    {
        return (new SubscriptionChart($this->request))->get();
    }

    public function getCustomerChart(): array
    {
        return (new CustomerChart($this->request))->get();
    }

    public function getOrderChart(): array
    {
        return (new OrderChart($this->request))->get();
    }

    public function getMrrChart(): array
    {
        return (new MrrChart($this->request))->get();
    }

    public function getChurnChart(): array
    {
        return (new ChurnChart($this->request))->get();
    }

    public function getRevenueBreakdown(): array
    {
        return (new RevenueBreakdownChart($this->request))->get();
    }

    public function getMembersBreakdown(): array
    {
        return (new MembersBreakdownChart($this->request))->get();
    }
}
