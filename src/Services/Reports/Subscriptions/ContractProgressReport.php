<?php

namespace Coderstm\Services\Reports\Subscriptions;

use Coderstm\Coderstm;
use Coderstm\Models\Subscription;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

/**
 * Contract Progress Report
 *
 * Tracks progress through contract-based subscriptions with fixed billing cycles.
 * Progress = (current_cycle / total_cycles) × 100. Near completion at ≥80% progress.
 */
class ContractProgressReport extends AbstractReport
{
    /**
     * {@inheritdoc}
     */
    protected array $columns = [
        'plan_name' => ['label' => 'Plan Name', 'type' => 'text'],
        'contract_cycles' => ['label' => 'Contract Cycles', 'type' => 'number'],
        'active_contracts' => ['label' => 'Active Contracts', 'type' => 'number'],
        'avg_current_cycle' => ['label' => 'Avg Current Cycle', 'type' => 'number'],
        'avg_progress' => ['label' => 'Avg Progress', 'type' => 'percentage'],
        'near_completion' => ['label' => 'Near Completion (>80%)', 'type' => 'number'],
        'completed_contracts' => ['label' => 'Completed Contracts', 'type' => 'number'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'contract-progress';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Track contract-based subscription progress and completion rates';
    }

    /**
     * Build efficient query with all aggregations in single query.
     *
     * Uses literal values in CASE statements for database compatibility.
     *
     * {@inheritdoc}
     */
    public function query(array $filters)
    {
        $now = now()->toDateTimeString();
        $plansQuery = Coderstm::$planModel::query()->select('*');

        return DB::table(DB::raw("({$plansQuery->toSql()}) as plans"))
            ->mergeBindings($plansQuery->toBase())
            ->leftJoinSub(
                Subscription::query()->select('*'),
                'subscriptions',
                function ($join) {
                    $join->on('subscriptions.plan_id', '=', 'plans.id')
                        ->whereNotNull('subscriptions.total_cycles')
                        ->where('subscriptions.total_cycles', '>', 0);
                }
            )
            ->whereNotNull('plans.contract_cycles')
            ->where('plans.contract_cycles', '>', 0)
            ->select([
                'plans.id',
                'plans.label as plan_name',
                'plans.contract_cycles',
                DB::raw("COUNT(DISTINCT CASE
                    WHEN subscriptions.canceled_at IS NULL
                    AND (subscriptions.expires_at IS NULL OR subscriptions.expires_at > '{$now}')
                    THEN subscriptions.id
                END) as active_contracts"),
                DB::raw('AVG(CASE
                    WHEN subscriptions.canceled_at IS NULL
                    THEN subscriptions.current_cycle
                END) as avg_current_cycle'),
                DB::raw('AVG(CASE
                    WHEN subscriptions.canceled_at IS NULL AND subscriptions.total_cycles > 0
                    THEN subscriptions.current_cycle * 100.0 / subscriptions.total_cycles
                END) as avg_progress'),
                DB::raw('COUNT(DISTINCT CASE
                    WHEN subscriptions.canceled_at IS NULL
                    AND subscriptions.current_cycle >= subscriptions.total_cycles * 0.8
                    THEN subscriptions.id
                END) as near_completion'),
                DB::raw('COUNT(DISTINCT CASE
                    WHEN subscriptions.current_cycle >= subscriptions.total_cycles
                    THEN subscriptions.id
                END) as completed_contracts'),
            ])
            ->groupBy('plans.id', 'plans.label', 'plans.contract_cycles')
            ->orderBy('plans.label');
    }

    /**
     * {@inheritdoc}
     */
    public function toRow($row): array
    {
        return [
            'plan_name' => $row->plan_name ?? '',
            'contract_cycles' => (int) ($row->contract_cycles ?? 0),
            'active_contracts' => (int) ($row->active_contracts ?? 0),
            'avg_current_cycle' => (float) ($row->avg_current_cycle ?? 0),
            'avg_progress' => (float) ((float) ($row->avg_progress ?? 0)),
            'near_completion' => (int) ($row->near_completion ?? 0),
            'completed_contracts' => (int) ($row->completed_contracts ?? 0),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function summarize(array $filters): array
    {
        $now = now()->toDateTimeString();

        $summary = Subscription::query()->toBase()
            ->whereNotNull('total_cycles')
            ->where('total_cycles', '>', 0)
            ->select([
                DB::raw("COUNT(CASE
                    WHEN canceled_at IS NULL
                    AND (expires_at IS NULL OR expires_at > '{$now}')
                    THEN 1
                END) as total_active_contracts"),
                DB::raw('COUNT(CASE
                    WHEN current_cycle >= total_cycles
                    THEN 1
                END) as total_completed_contracts'),
            ])
            ->first();

        return [
            'total_active_contracts' => (int) ($summary->total_active_contracts ?? 0),
            'total_completed_contracts' => (int) ($summary->total_completed_contracts ?? 0),
        ];
    }
}
