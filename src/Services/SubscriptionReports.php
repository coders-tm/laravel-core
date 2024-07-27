<?php

namespace Coderstm\Services;

use Illuminate\Http\Request;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionReports
{
    protected $request;
    protected $column;

    public function __construct(Request $request, $column = 'subscription_invoices.created_at')
    {
        $this->request = $request;
        $this->column = $column;
    }

    public function query()
    {
        $query = Subscription::select(
            'subscriptions.*',
            DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
            'plans.price as plan_price',
            'plans.label as plan_label',
            DB::raw("SUM(CASE WHEN subscription_invoices.status = 'paid' THEN subscription_invoices.grand_total ELSE 0 END) AS total_paid")
        )
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->leftJoin('subscription_invoices', 'subscriptions.id', '=', 'subscription_invoices.subscription_id');

        if ($this->request->filled('year')) {
            $query->whereYear($this->column, $this->request->year);
        }
        if ($this->request->filled('month')) {
            $query->whereMonth($this->column, $this->request->month);
        }
        if ($this->request->filled('day')) {
            $query->whereDay($this->column, $this->request->day);
        }

        return $query->groupBy(
            'users.id',
            'users.first_name',
            'users.last_name',
            'subscriptions.id',
            'subscriptions.status',
            'plans.price',
            'plans.label',
            'plans.id'
        )->havingRaw('COUNT(subscriptions.id) > 0');
    }

    public function count()
    {
        return $this->query()->count();
    }

    public function sum($column = '')
    {
        return $this->query()->sum($column);
    }

    public function onlyRolling()
    {
        return $this->query()->where('subscriptions.status', Subscription::STATUS_ACTIVE)
            ->whereNull('subscriptions.cancels_at');
    }

    public function onlyEnds()
    {
        return $this->query()->where('subscriptions.status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('subscriptions.cancels_at');
    }

    public function onlyFree()
    {
        return $this->query()->where('subscriptions.status', Subscription::STATUS_ACTIVE)
            ->where('plans.price', 0);
    }

    public function onlyCancelled()
    {
        return $this->query()->where('subscriptions.status', '<>', Subscription::STATUS_ACTIVE);
    }
}
