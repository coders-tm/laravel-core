<?php

namespace Coderstm\Services\Reports\Subscriptions;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class FreezeUsageReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'total_freezes' => ['label' => 'Total Freezes', 'type' => 'number'], 'currently_frozen' => ['label' => 'Currently Frozen', 'type' => 'number'], 'avg_freeze_duration' => ['label' => 'Avg Freeze Duration (days)', 'type' => 'number'], 'freezes_released' => ['label' => 'Freezes Released', 'type' => 'number'], 'freeze_rate' => ['label' => 'Freeze Rate', 'type' => 'percentage']];

    public static function getType(): string
    {
        return 'freeze-usage';
    }

    public function getDescription(): string
    {
        return 'Monitor subscription freezes and pause/resume patterns';
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
        $daysExpression = $this->dbDateDiff('release_subs.release_at', 'release_subs.frozen_at');

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->leftJoin('subscriptions as freeze_subs', function ($join) {
            $join->whereNotNull('freeze_subs.frozen_at')->whereRaw('freeze_subs.frozen_at >= periods.period_start')->whereRaw('freeze_subs.frozen_at <= periods.period_end');
        })->leftJoin('subscriptions as release_subs', function ($join) {
            $join->whereNotNull('release_subs.release_at')->whereNotNull('release_subs.frozen_at')->whereRaw('release_subs.release_at >= periods.period_start')->whereRaw('release_subs.release_at <= periods.period_end');
        })->leftJoin('subscriptions as frozen_at_end', function ($join) {
            $join->whereNotNull('frozen_at_end.frozen_at')->whereRaw('frozen_at_end.frozen_at <= periods.period_end')->where(function ($q) {
                $q->whereNull('frozen_at_end.release_at')->orWhereRaw('frozen_at_end.release_at > periods.period_end');
            });
        })->leftJoin('subscriptions as active_subs', function ($join) {
            $join->whereRaw('active_subs.created_at <= periods.period_end')->whereRaw("active_subs.status = 'active'");
        })->select(['periods.period_start', 'periods.period_order', DB::raw('COUNT(DISTINCT freeze_subs.id) as total_freezes'), DB::raw('COUNT(DISTINCT frozen_at_end.id) as currently_frozen'), DB::raw('COUNT(DISTINCT release_subs.id) as freezes_released'), DB::raw('COUNT(DISTINCT active_subs.id) as total_active'), DB::raw("COALESCE(AVG({$daysExpression}), 0) as avg_freeze_duration")])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start));
        $totalFreezes = (int) ($row->total_freezes ?? 0);
        $totalActive = (int) ($row->total_active ?? 0);
        $freezeRate = $totalActive > 0 ? $totalFreezes / $totalActive * 100 : 0;

        return ['period' => $period, 'total_freezes' => $totalFreezes, 'currently_frozen' => (int) ($row->currently_frozen ?? 0), 'avg_freeze_duration' => (float) ($row->avg_freeze_duration ?? 0), 'freezes_released' => (int) ($row->freezes_released ?? 0), 'freeze_rate' => (float) $freezeRate];
    }

    public function summarize(array $filters): array
    {
        $now = now()->toDateTimeString();
        $currentlyFrozen = DB::table('subscriptions')->whereNotNull('frozen_at')->where(function ($q) use ($now) {
            $q->whereNull('release_at')->orWhereRaw('release_at > ?', [$now]);
        })->count();

        return ['currently_frozen' => (int) $currentlyFrozen];
    }
}
