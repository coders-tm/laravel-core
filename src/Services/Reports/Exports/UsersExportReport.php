<?php

namespace Coderstm\Services\Reports\Exports;

use Coderstm\Coderstm;
use Coderstm\Enum\AppStatus;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class UsersExportReport extends AbstractReport
{
    protected array $columns = ['id' => ['label' => 'ID', 'type' => 'text'], 'name' => ['label' => 'Name', 'type' => 'text'], 'email' => ['label' => 'Email', 'type' => 'text'], 'status' => ['label' => 'Status', 'type' => 'text'], 'plan' => ['label' => 'Plan', 'type' => 'text'], 'subscription_status' => ['label' => 'Subscription Status', 'type' => 'text'], 'trial_ends_at' => ['label' => 'Trial Ends At', 'type' => 'text'], 'created_at' => ['label' => 'Created At', 'type' => 'text']];

    public static function getType(): string
    {
        return 'users';
    }

    public function getDescription(): string
    {
        return 'Export all user data with customizable fields';
    }

    public function query(array $filters)
    {
        $userTable = (new Coderstm::$userModel)->getTable();
        $subscriptionSubquery = DB::table('subscriptions as s')->select(['s.user_id', 's.type as plan_type', 's.status as sub_status', 's.trial_ends_at', DB::raw('MAX(s.created_at) as latest_sub_date')])->where(function ($q) {
            $q->where('s.status', AppStatus::ACTIVE->value)->orWhereNull('s.canceled_at');
        })->groupBy('s.user_id', 's.type', 's.status', 's.trial_ends_at');

        return DB::table("{$userTable} as users")->leftJoin(DB::raw("({$subscriptionSubquery->toSql()}) as subscription"), 'users.id', '=', 'subscription.user_id')->mergeBindings($subscriptionSubquery)->select(['users.id', 'users.name', 'users.email', 'users.status', 'users.created_at', 'subscription.plan_type', 'subscription.sub_status', 'subscription.trial_ends_at'])->orderBy('users.created_at', 'desc');
    }

    public function toRow($row): array
    {
        return ['id' => $row->id, 'name' => $row->name ?? '', 'email' => $row->email ?? '', 'status' => $row->status ?? 'unknown', 'plan' => $row->plan_type ?? 'No Plan', 'subscription_status' => $row->sub_status ?? 'none', 'trial_ends_at' => $row->trial_ends_at ?? '', 'created_at' => $row->created_at ?? ''];
    }

    public function summarize(array $filters): array
    {
        $userTable = (new Coderstm::$userModel)->getTable();
        $stats = DB::table("{$userTable} as users")->select([DB::raw('COUNT(*) as total_users')])->first();

        return ['total_users' => (int) $stats->total_users];
    }
}
