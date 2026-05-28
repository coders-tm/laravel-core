<?php

namespace Coderstm\Services\Reports\Checkout;

use Coderstm\Models\Payment;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class PaymentMethodPerformanceReport extends AbstractReport
{
    protected array $columns = ['payment_provider' => ['label' => 'Payment Provider', 'type' => 'text'], 'total_attempts' => ['label' => 'Total Attempts', 'type' => 'number'], 'successful' => ['label' => 'Successful', 'type' => 'number'], 'failed' => ['label' => 'Failed', 'type' => 'number'], 'success_rate' => ['label' => 'Success Rate', 'type' => 'percentage'], 'total_amount' => ['label' => 'Total Amount', 'type' => 'currency'], 'avg_amount' => ['label' => 'Avg Amount', 'type' => 'currency'], 'avg_processing_time' => ['label' => 'Avg Processing Time (sec)', 'type' => 'number']];

    public static function getType(): string
    {
        return 'payment-method-performance';
    }

    public function getDescription(): string
    {
        return 'Compare payment method success rates and performance metrics';
    }

    public function query(array $filters)
    {
        $dateDiffDays = $this->dbDateDiff('payments.processed_at', 'payments.created_at');

        return DB::table('payments')->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')->whereBetween('payments.created_at', [$filters['from'], $filters['to']])->select(['payment_methods.provider as payment_provider', DB::raw('COUNT(*) as total_attempts'), DB::raw("SUM(CASE WHEN payments.status = '".Payment::STATUS_COMPLETED."' THEN 1 ELSE 0 END) as successful"), DB::raw("SUM(CASE WHEN payments.status = '".Payment::STATUS_FAILED."' THEN 1 ELSE 0 END) as failed"), DB::raw("COALESCE(SUM(CASE WHEN payments.status = '".Payment::STATUS_COMPLETED."' THEN payments.amount ELSE 0 END), 0) as total_amount"), DB::raw("COALESCE(AVG(CASE WHEN payments.status = '".Payment::STATUS_COMPLETED."' THEN payments.amount ELSE NULL END), 0) as avg_amount"), DB::raw("COALESCE(AVG(CASE WHEN payments.processed_at IS NOT NULL THEN ({$dateDiffDays}) * 86400 END), 0) as avg_processing_time")])->groupBy('payment_methods.provider')->orderBy('payment_methods.provider');
    }

    public function toRow($row): array
    {
        $successRate = $row->total_attempts > 0 ? $row->successful / $row->total_attempts * 100 : 0;

        return ['payment_provider' => ucfirst($row->payment_provider ?? ''), 'total_attempts' => (int) ($row->total_attempts ?? 0), 'successful' => (int) ($row->successful ?? 0), 'failed' => (int) ($row->failed ?? 0), 'success_rate' => (float) $successRate, 'total_amount' => (float) ($row->total_amount ?? 0), 'avg_amount' => (float) ($row->avg_amount ?? 0), 'avg_processing_time' => $this->formatNumber($row->avg_processing_time ?? 0, 1)];
    }

    public function summarize(array $filters): array
    {
        $stats = DB::table('payments')->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')->whereBetween('payments.created_at', [$filters['from'], $filters['to']])->select([DB::raw('COUNT(*) as total_attempts'), DB::raw("SUM(CASE WHEN status = '".Payment::STATUS_COMPLETED."' THEN 1 ELSE 0 END) as successful"), DB::raw("COALESCE(SUM(CASE WHEN status = '".Payment::STATUS_COMPLETED."' THEN amount ELSE 0 END), 0) as total_amount")])->first();

        return ['total_attempts' => (int) $stats->total_attempts, 'successful' => (int) $stats->successful, 'total_amount' => (float) $stats->total_amount, 'overall_success_rate' => (float) ($stats->total_attempts > 0 ? $stats->successful / $stats->total_attempts * 100 : 0)];
    }
}
