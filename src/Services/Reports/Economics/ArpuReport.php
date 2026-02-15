<?php

namespace Coderstm\Services\Reports\Economics;

use Coderstm\Models\Shop\Order;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class ArpuReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'total_revenue' => ['label' => 'Total Revenue', 'type' => 'currency'], 'active_users' => ['label' => 'Active Users', 'type' => 'number'], 'arpu' => ['label' => 'ARPU', 'type' => 'currency']];

    public static function getType(): string
    {
        return 'arpu';
    }

    public function getDescription(): string
    {
        return 'Track average revenue per user and trends';
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

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->leftJoin(DB::raw('orders'), function ($join) {
            $join->whereRaw('DATE(orders.created_at) BETWEEN DATE(periods.period_start) AND DATE(periods.period_end)')->whereRaw("orders.payment_status = '".Order::STATUS_PAID."'");
        })->leftJoin(DB::raw('subscriptions'), function ($join) {
            $join->whereRaw('DATE(subscriptions.created_at) <= DATE(periods.period_end)')->whereRaw('subscriptions.canceled_at IS NULL')->where(function ($q) {
                $q->whereRaw('subscriptions.expires_at IS NULL')->orWhereRaw('DATE(subscriptions.expires_at) >= DATE(periods.period_start)');
            });
        })->select(['periods.period_start', 'periods.period_end', 'periods.period_order', DB::raw('COALESCE(SUM(orders.grand_total), 0) as total_revenue'), DB::raw('COUNT(DISTINCT subscriptions.user_id) as active_users')])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $totalRevenue = (float) ($row->total_revenue ?? 0);
        $activeUsers = (int) ($row->active_users ?? 0);
        $arpu = $activeUsers > 0 ? $totalRevenue / $activeUsers : 0;
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start)) ?? '';

        return ['period' => $period, 'total_revenue' => $totalRevenue, 'active_users' => $activeUsers, 'arpu' => (float) $arpu];
    }

    public function summarize(array $filters): array
    {
        $totalRevenue = DB::table('orders')->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$filters['from'], $filters['to']])->sum('grand_total');
        $activeUsers = DB::table('subscriptions')->where('created_at', '<=', $filters['to'])->where(function ($q) use ($filters) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>=', $filters['from']);
        })->whereNull('canceled_at')->distinct('user_id')->count('user_id');
        $averageArpu = $activeUsers > 0 ? $totalRevenue / $activeUsers : 0;

        return ['total_revenue' => format_amount($totalRevenue), 'total_active_users' => (int) $activeUsers, 'average_arpu' => format_amount($averageArpu)];
    }
}
