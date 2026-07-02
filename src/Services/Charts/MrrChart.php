<?php

namespace Coderstm\Services\Charts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MrrChart extends AbstractChart
{
    public function get(): array
    {
        $labels = $this->getMonthLabels();
        $formattedData = [];
        for ($i = 0; $i < count($labels); $i++) {
            $date = Carbon::now()->subMonths($this->months - 1 - $i)->endOfMonth();
            $mrr = DB::table('subscriptions')->join('plans', 'subscriptions.plan_id', '=', 'plans.id')->where('subscriptions.status', 'active')->where('subscriptions.created_at', '<=', $date)->where(function ($q) use ($date) {
                $q->whereNull('subscriptions.canceled_at')->orWhere('subscriptions.expires_at', '>', $date);
            })->sum(DB::raw("\n                    CASE plans.interval\n                        WHEN 'day' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 30\n                        WHEN 'week' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 4.345\n                        WHEN 'month' THEN (plans.price / COALESCE(plans.interval_count, 1))\n                        WHEN 'year' THEN (plans.price / COALESCE(plans.interval_count, 1)) / 12\n                        ELSE 0\n                    END * COALESCE(subscriptions.quantity, 1)\n                ")) ?? 0.0;
            $formattedData[$labels[$i]] = round($mrr, 2);
        }

        return $formattedData;
    }
}
