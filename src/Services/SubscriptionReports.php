<?php

namespace Coderstm\Services;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Coderstm\Services\Subscription;

class SubscriptionReports
{
    protected $request;
    protected $column;

    public static $subscriptionModel = Subscription::class;

    public function __construct(Request $request, $column = 'created_at')
    {
        $this->request = $request;
        $this->column = $column;
    }

    public static function useSubscriptionModel($model)
    {
        static::$subscriptionModel = $model;
    }

    public function query($table = 'subscriptions')
    {
        $query = static::$subscriptionModel::query();

        if ($this->request->filled('year')) {
            $query->whereYear("$table.{$this->column}", $this->request->year);
        }
        if ($this->request->filled('month')) {
            $query->whereMonth("$table.{$this->column}", $this->request->month);
        }
        if ($this->request->filled('day')) {
            $query->whereDay("$table.{$this->column}", $this->request->day);
        }

        return $query;
    }

    public function count()
    {
        // Cache the count result to improve performance
        return Cache::remember('subscription_count_' . $this->request->fullUrl(), 60, function () {
            return $this->query()->count();
        });
    }

    public function sumOfPayments()
    {
        return $this->query('orders')
            ->leftJoin('orders', function ($join) {
                $join->on('orders.orderable_id', '=', 'subscriptions.id')
                    ->where('orders.orderable_type', '=', Coderstm::$subscriptionModel);
            })
            ->leftJoin('statuses', function ($join) {
                $join->on('statuses.statusable_id', '=', 'orders.id')
                    ->where('statuses.statusable_type', '=', Coderstm::$orderModel)
                    ->where('statuses.label', 'Paid');
            })
            ->sum('orders.grand_total');
    }

    public function onlyRolling()
    {
        return $this->query()->active();
    }

    public function sumOfRolling()
    {
        return $this->onlyRolling()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');
    }

    public function onlyEnds()
    {
        return $this->query()->ended();
    }

    public function sumOfEnds()
    {
        return $this->onlyEnds()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');
    }

    public function onlyFree()
    {
        return $this->onlyRolling()->free();
    }

    public function onlyCancelled()
    {
        return $this->query()->canceled();
    }

    public function sumOfCancelled()
    {
        return $this->onlyCancelled()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');
    }
}
