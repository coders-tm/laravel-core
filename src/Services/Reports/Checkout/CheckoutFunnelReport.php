<?php

namespace Coderstm\Services\Reports\Checkout;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class CheckoutFunnelReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'started' => ['label' => 'Checkouts Started', 'type' => 'number'], 'contact_filled' => ['label' => 'Contact Info Filled', 'type' => 'number'], 'billing_added' => ['label' => 'Billing Added', 'type' => 'number'], 'payment_attempted' => ['label' => 'Payment Attempted', 'type' => 'number'], 'payment_succeeded' => ['label' => 'Payment Succeeded', 'type' => 'number'], 'completed' => ['label' => 'Completed', 'type' => 'number'], 'abandoned' => ['label' => 'Abandoned', 'type' => 'number'], 'start_to_complete_rate' => ['label' => 'Start to Complete Rate', 'type' => 'percentage'], 'payment_success_rate' => ['label' => 'Payment Success Rate', 'type' => 'percentage']];

    public static function getType(): string
    {
        return 'checkout-funnel';
    }

    public function getDescription(): string
    {
        return 'Visualize checkout completion rates at each funnel stage';
    }

    public function query(array $filters)
    {
        $periods = $this->getDatePeriods();
        $periodBoundaries = [];
        foreach ($periods as $index => $periodStart) {
            $periodEnd = $this->getPeriodEnd($periodStart);
            $periodBoundaries[] = ['start' => $periodStart->toDateTimeString(), 'end' => $periodEnd->toDateTimeString(), 'order' => $index];
        }
        $periodQuery = $this->buildPeriodBoundariesQuery($periodBoundaries);
        if ($periodQuery === null) {
            return $this->emptyQuery();
        }

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->leftJoin(DB::raw('checkouts'), function ($join) {
            $join->whereRaw('checkouts.started_at BETWEEN periods.period_start AND periods.period_end');
        })->select(['periods.period_start', 'periods.period_order', DB::raw('COUNT(checkouts.id) as started'), DB::raw('COUNT(CASE WHEN checkouts.email IS NOT NULL THEN 1 END) as contact_filled'), DB::raw('COUNT(CASE WHEN checkouts.billing_address IS NOT NULL THEN 1 END) as billing_added'), DB::raw("COUNT(CASE WHEN checkouts.status IN ('pending', 'completed', 'failed') THEN 1 END) as payment_attempted"), DB::raw("COUNT(CASE WHEN checkouts.status = 'completed' THEN 1 END) as payment_succeeded"), DB::raw("COUNT(CASE WHEN checkouts.status = 'completed' AND checkouts.completed_at IS NOT NULL THEN 1 END) as completed"), DB::raw("COUNT(CASE WHEN checkouts.status = 'abandoned' THEN 1 END) as abandoned")])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start));
        $started = (int) ($row->started ?? 0);
        $completed = (int) ($row->completed ?? 0);
        $paymentAttempted = (int) ($row->payment_attempted ?? 0);
        $paymentSucceeded = (int) ($row->payment_succeeded ?? 0);
        $startToCompleteRate = $started > 0 ? $completed / $started * 100 : 0;
        $paymentSuccessRate = $paymentAttempted > 0 ? $paymentSucceeded / $paymentAttempted * 100 : 0;

        return ['period' => $period, 'started' => $started, 'contact_filled' => (int) ($row->contact_filled ?? 0), 'billing_added' => (int) ($row->billing_added ?? 0), 'payment_attempted' => $paymentAttempted, 'payment_succeeded' => $paymentSucceeded, 'completed' => $completed, 'abandoned' => (int) ($row->abandoned ?? 0), 'start_to_complete_rate' => (float) $startToCompleteRate, 'payment_success_rate' => (float) $paymentSuccessRate];
    }

    public function summarize(array $filters): array
    {
        $stats = DB::table('checkouts')->whereBetween('started_at', [$filters['from'], $filters['to']])->selectRaw("\n                COUNT(*) as started,\n                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed\n            ")->first();
        if (! $stats) {
            return ['started' => 0, 'completed' => 0, 'conversion_rate' => (float) 0];
        }
        $started = (int) $stats->started;
        $completed = (int) $stats->completed;

        return ['started' => $started, 'completed' => $completed, 'conversion_rate' => $started > 0 ? $completed / $started * 100 : 0];
    }
}
