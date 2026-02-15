<?php

namespace Coderstm\Services\Reports\Checkout;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class CheckoutRecoveryReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'abandoned_checkouts' => ['label' => 'Abandoned Checkouts', 'type' => 'number'], 'recovery_emails_sent' => ['label' => 'Recovery Emails Sent', 'type' => 'number'], 'recovered_checkouts' => ['label' => 'Recovered Checkouts', 'type' => 'number'], 'recovery_rate' => ['label' => 'Recovery Rate', 'type' => 'percentage'], 'recovered_revenue' => ['label' => 'Recovered Revenue', 'type' => 'currency'], 'lost_revenue' => ['label' => 'Lost Revenue', 'type' => 'currency'], 'avg_time_to_recover' => ['label' => 'Avg Time to Recover (hours)', 'type' => 'number']];

    public static function getType(): string
    {
        return 'checkout-recovery';
    }

    public function getDescription(): string
    {
        return 'Monitor abandoned checkout recovery email effectiveness';
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
        $dateDiffDays = $this->dbDateDiff('recovered.completed_at', 'recovered.recovery_email_sent_at');

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->leftJoin(DB::raw('checkouts as abandoned'), function ($join) {
            $join->whereRaw('abandoned.started_at BETWEEN periods.period_start AND periods.period_end')->whereRaw("abandoned.status = 'abandoned'");
        })->leftJoin(DB::raw('checkouts as recovered'), function ($join) {
            $join->whereRaw('recovered.started_at BETWEEN periods.period_start AND periods.period_end')->whereNotNull('recovered.recovery_email_sent_at')->whereRaw("recovered.status = 'completed'");
        })->select(['periods.period_start', 'periods.period_order', DB::raw('COUNT(DISTINCT abandoned.id) as abandoned_checkouts'), DB::raw('COUNT(DISTINCT CASE WHEN abandoned.recovery_email_sent_at IS NOT NULL THEN abandoned.id END) as recovery_emails_sent'), DB::raw('COUNT(DISTINCT recovered.id) as recovered_checkouts'), DB::raw('COALESCE(SUM(recovered.grand_total), 0) as recovered_revenue'), DB::raw('COALESCE(SUM(abandoned.grand_total), 0) as lost_revenue'), DB::raw("COALESCE(AVG(CASE WHEN recovered.completed_at IS NOT NULL THEN ({$dateDiffDays}) * 24 END), 0) as avg_time_to_recover")])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start));
        $recoveryRate = $row->recovery_emails_sent > 0 ? $row->recovered_checkouts / $row->recovery_emails_sent * 100 : 0;

        return ['period' => $period, 'abandoned_checkouts' => (int) ($row->abandoned_checkouts ?? 0), 'recovery_emails_sent' => (int) ($row->recovery_emails_sent ?? 0), 'recovered_checkouts' => (int) ($row->recovered_checkouts ?? 0), 'recovery_rate' => (float) $recoveryRate, 'recovered_revenue' => (float) ($row->recovered_revenue ?? 0), 'lost_revenue' => (float) ($row->lost_revenue ?? 0), 'avg_time_to_recover' => $this->formatNumber($row->avg_time_to_recover ?? 0, 1)];
    }

    public function summarize(array $filters): array
    {
        $stats = DB::table('checkouts')->whereBetween('started_at', [$filters['from'], $filters['to']])->whereNotNull('recovery_email_sent_at')->selectRaw("\n                COUNT(*) as recovery_emails_sent,\n                COUNT(CASE WHEN status = 'completed' THEN 1 END) as recovered_checkouts,\n                COALESCE(SUM(CASE WHEN status = 'completed' THEN grand_total END), 0) as recovered_revenue\n            ")->first();
        if (! $stats) {
            return ['recovery_emails_sent' => 0, 'recovered_checkouts' => 0, 'recovered_revenue' => (float) 0, 'recovery_rate' => (float) 0];
        }
        $recoveryEmailsSent = (int) $stats->recovery_emails_sent;
        $recoveredCheckouts = (int) $stats->recovered_checkouts;

        return ['recovery_emails_sent' => $recoveryEmailsSent, 'recovered_checkouts' => $recoveredCheckouts, 'recovered_revenue' => (float) $stats->recovered_revenue, 'recovery_rate' => $recoveryEmailsSent > 0 ? $recoveredCheckouts / $recoveryEmailsSent * 100 : 0];
    }
}
