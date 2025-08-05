<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Models\Payment;

class XenditPayment extends AbstractPayment
{
    public static function fromResponse(array $response, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        // Map Xendit statuses to our payment statuses
        $status = match ($response['status']) {
            'PAID', 'SETTLED', 'SUCCEEDED', 'COMPLETED' => Payment::STATUS_COMPLETED,
            'PENDING', 'PROCESSING' => Payment::STATUS_PROCESSING,
            'FAILED', 'EXPIRED' => Payment::STATUS_FAILED,
            'CANCELLED' => Payment::STATUS_CANCELLED,
            default => Payment::STATUS_FAILED,
        };

        $transactionId = $response['transaction_id'] ?? $response['id'] ?? 'unknown';
        $note = "Xendit Payment: {$transactionId} (Status: {$response['status']})";
        if (isset($response['failure_code'])) {
            $note .= " - Failure: {$response['failure_code']}";
        }
        if (isset($response['external_id'])) {
            $note .= " - External ID: {$response['external_id']}";
        }

        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $transactionId,
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: $status,
            note: $note,
            processedAt: new DateTime()
        );
    }

    public static function fromPaymentRequest(array $paymentRequest, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $paymentRequest['id'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: Payment::STATUS_PROCESSING,
            note: "Xendit Payment Request: {$paymentRequest['id']}",
            processedAt: new DateTime()
        );
    }
}
