<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Models\Payment;

class KlarnaPayment extends AbstractPayment
{
    public static function fromSession(array $session, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        $status = match ($session['status'] ?? 'incomplete') {
            'complete' => Payment::STATUS_COMPLETED,
            'incomplete' => Payment::STATUS_PENDING,
            'error' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };

        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $session['session_id'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: $status,
            note: "Klarna Session: {$session['session_id']}",
            processedAt: new DateTime()
        );
    }

    public static function fromOrder(array $order, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        // Klarna API doesn't return 'status', but 'fraud_status' and other indicators
        // For successful order creation, we can assume it's authorized
        $status = Payment::STATUS_PROCESSING; // Default to processing for authorized orders

        // Check fraud_status to determine if we should mark as failed
        if (isset($order['fraud_status']) && $order['fraud_status'] === 'REJECTED') {
            $status = Payment::STATUS_FAILED;
        }

        $orderStatus = $order['fraud_status'] ?? 'AUTHORIZED';
        $note = "Klarna Order: {$order['order_id']} - Fraud Status: {$orderStatus}";

        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $order['order_id'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: $status,
            note: $note,
            processedAt: new DateTime()
        );
    }
}
