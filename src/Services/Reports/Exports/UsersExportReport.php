<?php

namespace Coderstm\Services\Reports\Exports;

use Coderstm\Coderstm;
use Coderstm\Enum\AppStatus;
use Coderstm\Models\Subscription;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

/**
 * Users Export Report
 *
 * Exports user/admin data with subscription information via subquery JOIN.
 * Shows most recent active or uncanceled subscription. Multi-guard support for users and admins.
 */
class UsersExportReport extends AbstractReport
{
    /**
     * {@inheritdoc}
     */
    protected array $columns = [
        'id' => ['label' => 'ID', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'status' => ['label' => 'Status', 'type' => 'text'],
        'plan' => ['label' => 'Plan', 'type' => 'text'],
        'subscription_status' => ['label' => 'Subscription Status', 'type' => 'text'],
        'trial_ends_at' => ['label' => 'Trial Ends At', 'type' => 'text'],
        'created_at' => ['label' => 'Created At', 'type' => 'text'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Export all user data with customizable fields';
    }

    /**
     * Build the base query with active subscription JOIN.
     *
     * {@inheritdoc}
     */
    public function query(array $filters)
    {
        $subscriptionSubquery = Subscription::query()->toBase()
            ->select([
                'subscriptions.user_id',
                'subscriptions.type as plan_type',
                'subscriptions.status as sub_status',
                'subscriptions.trial_ends_at',
                DB::raw('MAX(subscriptions.created_at) as latest_sub_date'),
            ])
            ->where(function ($q) {
                $q->where('subscriptions.status', AppStatus::ACTIVE->value)
                    ->orWhereNull('subscriptions.canceled_at');
            })
            ->groupBy('subscriptions.user_id', 'subscriptions.type', 'subscriptions.status', 'subscriptions.trial_ends_at');

        return Coderstm::$userModel::query()->toBase()
            ->leftJoinSub($subscriptionSubquery, 'subscription', 'users.id', '=', 'subscription.user_id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.status',
                'users.created_at',
                'subscription.plan_type',
                'subscription.sub_status',
                'subscription.trial_ends_at',
            ])
            ->orderBy('users.created_at', 'desc');
    }

    /**
     * Transform row to array with raw values.
     *
     * No additional queries - subscription already joined.
     *
     * {@inheritdoc}
     */
    public function toRow($row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name ?? '',
            'email' => $row->email ?? '',
            'status' => $row->status ?? 'unknown',
            'plan' => $row->plan_type ?? 'No Plan',
            'subscription_status' => $row->sub_status ?? 'none',
            'trial_ends_at' => $row->trial_ends_at ?? '',
            'created_at' => $row->created_at ?? '',
        ];
    }

    /**
     * Calculate summary statistics.
     *
     * {@inheritdoc}
     */
    public function summarize(array $filters): array
    {
        $stats = Coderstm::$userModel::query()->toBase()
            ->select([DB::raw('COUNT(*) as total_users')])
            ->first();

        return [
            'total_users' => (int) $stats->total_users,
        ];
    }
}
