<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Models\Payment;

class PaystackPayment extends AbstractPayment
{
    public static function fromResponse(array $response, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        // Map Paystack statuses to our payment statuses
        $status = match ($response['status']) {
            'success', 'successful' => Payment::STATUS_COMPLETED,
            'pending', 'ongoing' => Payment::STATUS_PROCESSING,
            'failed', 'abandoned' => Payment::STATUS_FAILED,
            'cancelled' => Payment::STATUS_CANCELLED,
            default => Payment::STATUS_FAILED,
        };

        $note = "Paystack Transaction: {$response['reference']} (Status: {$response['status']})";
        if (isset($response['gateway_response'])) {
            $note .= " - {$response['gateway_response']}";
        }

        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $response['reference'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'NGN'),
            status: $status,
            note: $note,
            processedAt: new DateTime()
        );
    }

    public static function fromTransaction(array $transaction, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $transaction['reference'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'NGN'),
            status: Payment::STATUS_PROCESSING,
            note: "Paystack Transaction: {$transaction['reference']}",
            processedAt: new DateTime()
        );
    }
}
