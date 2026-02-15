<?php

namespace Coderstm\Services\Reports\Checkout;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class AbandonedCartDetailReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'total_abandoned' => ['label' => 'Total Abandoned', 'type' => 'number'], 'abandoned_value' => ['label' => 'Abandoned Value', 'type' => 'currency'], 'avg_cart_value' => ['label' => 'Avg Cart Value', 'type' => 'currency'], 'recovery_sent' => ['label' => 'Recovery Email Sent', 'type' => 'number'], 'recovered_after_email' => ['label' => 'Recovered After Email', 'type' => 'number'], 'recovery_rate' => ['label' => 'Recovery Rate', 'type' => 'percentage'], 'abandoned_at_contact' => ['label' => 'Abandoned at Contact', 'type' => 'number'], 'abandoned_at_billing' => ['label' => 'Abandoned at Billing', 'type' => 'number'], 'abandoned_at_payment' => ['label' => 'Abandoned at Payment', 'type' => 'number'], 'avg_time_to_abandon' => ['label' => 'Avg Time to Abandon (hours)', 'type' => 'number']];

    public static function getType(): string
    {
        return 'abandoned-cart-detail';
    }

    public function getDescription(): string
    {
        return 'Analyze abandoned checkout sessions and recovery metrics';
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
        $dateDiffDays = $this->dbDateDiff('abandoned.abandoned_at', 'abandoned.started_at');

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->leftJoin(DB::raw('checkouts as abandoned'), function ($join) {
            $join->whereRaw('abandoned.started_at BETWEEN periods.period_start AND periods.period_end')->whereRaw("abandoned.status = 'abandoned'");
        })->leftJoin(DB::raw('checkouts as recovered'), function ($join) {
            $join->whereRaw('recovered.started_at BETWEEN periods.period_start AND periods.period_end')->whereNotNull('recovered.recovery_email_sent_at')->whereRaw("recovered.status = 'completed'");
        })->select(['periods.period_start', 'periods.period_order', DB::raw('COUNT(DISTINCT abandoned.id) as total_abandoned'), DB::raw('COALESCE(SUM(abandoned.grand_total), 0) as abandoned_value'), DB::raw('COALESCE(AVG(abandoned.grand_total), 0) as avg_cart_value'), DB::raw('COUNT(DISTINCT CASE WHEN abandoned.recovery_email_sent_at IS NOT NULL THEN abandoned.id END) as recovery_sent'), DB::raw('COUNT(DISTINCT recovered.id) as recovered_after_email'), DB::raw('COUNT(DISTINCT CASE WHEN abandoned.email IS NOT NULL AND abandoned.billing_address IS NULL THEN abandoned.id END) as abandoned_at_contact'), DB::raw('COUNT(DISTINCT CASE WHEN abandoned.billing_address IS NOT NULL THEN abandoned.id END) as abandoned_at_billing'), DB::raw("COALESCE(AVG(CASE WHEN abandoned.abandoned_at IS NOT NULL THEN ({$dateDiffDays}) * 24 END), 0) as avg_time_to_abandon")])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start));
        $recoveryRate = $row->recovery_sent > 0 ? $row->recovered_after_email / $row->recovery_sent * 100 : 0;
        $abandonedAtPayment = $row->abandoned_at_billing;

        return ['period' => $period, 'total_abandoned' => (int) ($row->total_abandoned ?? 0), 'abandoned_value' => (float) ($row->abandoned_value ?? 0), 'avg_cart_value' => (float) ($row->avg_cart_value ?? 0), 'recovery_sent' => (int) ($row->recovery_sent ?? 0), 'recovered_after_email' => (int) ($row->recovered_after_email ?? 0), 'recovery_rate' => (float) $recoveryRate, 'abandoned_at_contact' => (int) ($row->abandoned_at_contact ?? 0), 'abandoned_at_billing' => (int) ($row->abandoned_at_billing ?? 0), 'abandoned_at_payment' => (int) $abandonedAtPayment, 'avg_time_to_abandon' => $this->formatNumber($row->avg_time_to_abandon ?? 0, 1)];
    }

    public function summarize(array $filters): array
    {
        $stats = DB::table('checkouts')->whereBetween('started_at', [$filters['from'], $filters['to']])->where('status', 'abandoned')->selectRaw('
                COUNT(*) as total_abandoned,
                COALESCE(SUM(grand_total), 0) as abandoned_value,
                COUNT(CASE WHEN recovery_email_sent_at IS NOT NULL THEN 1 END) as recovery_sent
            ')->first();
        if (! $stats) {
            return ['total_abandoned' => 0, 'abandoned_value' => format_amount(0), 'recovery_sent' => 0];
        }

        return ['total_abandoned' => (int) $stats->total_abandoned, 'abandoned_value' => format_amount($stats->abandoned_value), 'recovery_sent' => (int) $stats->recovery_sent];
    }
}
