<?php

namespace Coderstm\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Coderstm\Models\Cashier\Subscription;
use Stripe\Subscription as StripeSubscription;

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
            'plans.id as plan_id',
            DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
            'plan_prices.amount as plan_price',
            'plans.label as plan_label',
            DB::raw("SUM(CASE WHEN subscription_invoices.stripe_status = 'paid' THEN (subscription_invoices.total/100) ELSE 0 END) AS total_paid")
        )
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->join('plan_prices', 'subscriptions.stripe_price', '=', 'plan_prices.stripe_id')
            ->join('plans', 'plan_prices.plan_id', '=', 'plans.id')
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
            'subscriptions.stripe_status',
            'plan_prices.amount',
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
        return $this->query()->where('subscriptions.stripe_status', StripeSubscription::STATUS_ACTIVE)
            ->whereNull('subscriptions.cancels_at');
    }

    public function onlyEnds()
    {
        return $this->query()->where('subscriptions.stripe_status', StripeSubscription::STATUS_ACTIVE)
            ->whereNotNull('subscriptions.cancels_at');
    }

    public function onlyFree()
    {
        return $this->query()->where('subscriptions.stripe_status', StripeSubscription::STATUS_ACTIVE)
            ->where('plan_prices.amount', 0);
    }

    public function onlyCancelled()
    {
        return $this->query()->where('subscriptions.stripe_status', '<>', StripeSubscription::STATUS_ACTIVE);
    }
}
