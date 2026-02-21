<?php

namespace Coderstm\Services\Reports\Exports;

use Coderstm\Services\Reports\AbstractReport;

class CheckoutsExportReport extends AbstractReport
{
    protected array $columns = ['id' => ['label' => 'Checkout ID', 'type' => 'text'], 'token' => ['label' => 'Token', 'type' => 'text'], 'type' => ['label' => 'Type', 'type' => 'text'], 'status' => ['label' => 'Status', 'type' => 'text'], 'email' => ['label' => 'Email', 'type' => 'text'], 'first_name' => ['label' => 'First Name', 'type' => 'text'], 'last_name' => ['label' => 'Last Name', 'type' => 'text'], 'phone' => ['label' => 'Phone', 'type' => 'text'], 'sub_total' => ['label' => 'Sub Total', 'type' => 'currency'], 'discount_total' => ['label' => 'Discount Total', 'type' => 'currency'], 'tax_total' => ['label' => 'Tax Total', 'type' => 'currency'], 'shipping_total' => ['label' => 'Shipping Total', 'type' => 'currency'], 'grand_total' => ['label' => 'Grand Total', 'type' => 'currency'], 'coupon_code' => ['label' => 'Coupon Code', 'type' => 'text'], 'payment_provider' => ['label' => 'Payment Provider', 'type' => 'text'], 'user_id' => ['label' => 'User ID', 'type' => 'number'], 'order_id' => ['label' => 'Order ID', 'type' => 'number'], 'started_at' => ['label' => 'Started At', 'type' => 'text'], 'abandoned_at' => ['label' => 'Abandoned At', 'type' => 'text'], 'completed_at' => ['label' => 'Completed At', 'type' => 'text'], 'recovery_email_sent' => ['label' => 'Recovery Email Sent', 'type' => 'text']];

    public static function getType(): string
    {
        return 'checkouts';
    }

    public function getDescription(): string
    {
        return 'Export checkout session data';
    }

    public function query(array $filters)
    {
        return $this->buildCheckoutsQuery()->orderBy('started_at', 'desc');
    }

    public function toRow($row): array
    {
        return ['id' => (int) $row->id, 'token' => $row->token ?? '', 'type' => $row->type ?? 'standard', 'status' => $row->status ?? '', 'email' => $row->email ?? '', 'first_name' => $row->first_name ?? '', 'last_name' => $row->last_name ?? '', 'phone' => $row->phone_number ?? '', 'sub_total' => (float) ($row->sub_total ?? 0), 'discount_total' => (float) ($row->discount_total ?? 0), 'tax_total' => (float) ($row->tax_total ?? 0), 'shipping_total' => (float) ($row->shipping_total ?? 0), 'grand_total' => (float) ($row->grand_total ?? 0), 'coupon_code' => $row->coupon_code ?? '', 'payment_provider' => $row->payment_provider ?? '', 'user_id' => $row->user_id ? (int) $row->user_id : null, 'order_id' => $row->order_id ? (int) $row->order_id : null, 'started_at' => $row->started_at ?? '', 'abandoned_at' => $row->abandoned_at ?? '', 'completed_at' => $row->completed_at ?? '', 'recovery_email_sent' => $row->recovery_email_sent_at ? 'Yes' : 'No'];
    }

    public function summarize(array $filters): array
    {
        $query = $this->buildCheckoutsQuery();

        return ['total_checkouts' => (int) $query->count()];
    }
}
