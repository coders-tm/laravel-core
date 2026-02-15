<?php

namespace Coderstm\Payment\Mappers;

use Coderstm\Models\Payment;
use Coderstm\Models\PaymentMethod;
use DateTime;

class KlarnaPayment extends AbstractPayment
{
    public function __construct($response, PaymentMethod $paymentMethod)
    {
        if (is_object($response)) {
            $response = json_decode(json_encode($response), true);
        }
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $response['order_id'] ?? $response['session_id'] ?? uniqid('klarna_');
        $this->amount = ($response['order_amount'] ?? 0) / 100;
        $this->currency = strtoupper($response['purchase_currency'] ?? config('app.currency', 'USD'));
        if (isset($response['session_id'])) {
            $this->status = match ($response['status'] ?? 'incomplete') {
                'complete' => Payment::STATUS_COMPLETED,
                'incomplete' => Payment::STATUS_PENDING,
                'error' => Payment::STATUS_FAILED,
                default => Payment::STATUS_PENDING,
            };
            $this->note = "Klarna Session: {$this->transactionId}";
        } else {
            $fraudStatus = $response['fraud_status'] ?? 'AUTHORIZED';
            $this->status = $fraudStatus === 'REJECTED' ? Payment::STATUS_FAILED : Payment::STATUS_PROCESSING;
            $this->note = "Klarna Order: {$this->transactionId} - Fraud Status: {$fraudStatus}";
        }
        $this->processedAt = new DateTime;
        $this->metadata = $this->extractMetadata($response);
    }

    protected function extractMetadata($data): array
    {
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }
        $normalized = ['payment_method_type' => 'klarna'];
        if (isset($data['payment_method_category'])) {
            $normalized['klarna_category'] = $data['payment_method_category'];
        }
        if (isset($data['billing_address'])) {
            $address = $data['billing_address'];
            $normalized['country'] = $address['country'] ?? null;
        }
        if (isset($data['customer'])) {
            $normalized['customer_email'] = $data['customer']['email'] ?? null;
        }
        $normalized['payment_method'] = $this->buildDisplayString($normalized);

        return array_filter($normalized);
    }

    private function buildDisplayString(array $metadata): string
    {
        $category = $metadata['klarna_category'] ?? null;
        if ($category) {
            $displayNames = ['pay_later' => 'Klarna Pay Later', 'pay_over_time' => 'Klarna Financing', 'pay_now' => 'Klarna Pay Now', 'direct_bank_transfer' => 'Klarna Bank Transfer', 'direct_debit' => 'Klarna Direct Debit'];

            return $displayNames[$category] ?? 'Klarna - '.ucwords(str_replace('_', ' ', $category));
        }

        return 'Klarna';
    }
}
