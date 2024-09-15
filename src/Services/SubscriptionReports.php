<?php

namespace Coderstm\Services;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionReports
{
    protected $request;
    protected $column;

    public function __construct(Request $request, $column = 'orders.created_at')
    {
        $this->request = $request;
        $this->column = $column;
    }

    public function query()
    {
        $query = Subscription::query();

        if ($this->request->filled('year')) {
            $query->whereYear($this->column, $this->request->year);
        }
        if ($this->request->filled('month')) {
            $query->whereMonth($this->column, $this->request->month);
        }
        if ($this->request->filled('day')) {
            $query->whereDay($this->column, $this->request->day);
        }

        $query->select(
            'subscriptions.id',
            'subscriptions.status',
            'subscriptions.ends_at',
            DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
            'plans.price as plan_price',
            'plans.label as plan_label',
            DB::raw("SUM(CASE WHEN statuses.label = 'Paid' THEN orders.grand_total ELSE 0 END) AS total_paid")
        )
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->leftJoin('orders', function ($join) {
                $join->on('orders.orderable_id', '=', "subscriptions.id")
                    ->where('orders.orderable_type', '=', Subscription::class);
            })
            ->leftJoin('statuses', function ($join) {
                $join->on('statuses.statusable_id', '=', "orders.id")
                    ->where('statuses.statusable_type', '=', Coderstm::$orderModel);
            });

        return $query->groupBy(
            'users.id',
            'users.first_name',
            'users.last_name',
            'subscriptions.id',
            'subscriptions.status',
            'plans.price',
            'plans.label',
            'plans.id',
            'orders.id',
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
