<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Models\Payment;

class MercadoPagoPayment extends AbstractPayment
{
    public static function fromResponse(array $response, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        // Map MercadoPago statuses to our payment statuses
        $status = match ($response['status']) {
            'approved' => Payment::STATUS_COMPLETED,
            'pending', 'in_process' => Payment::STATUS_PROCESSING,
            'rejected', 'cancelled' => Payment::STATUS_FAILED,
            'refunded', 'charged_back' => Payment::STATUS_CANCELLED,
            default => Payment::STATUS_FAILED,
        };

        $note = "MercadoPago Payment: {$response['id']} (Status: {$response['status']})";
        if (isset($response['status_detail'])) {
            $note .= " - {$response['status_detail']}";
        }

        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $response['id'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: $status,
            note: $note,
            processedAt: new DateTime()
        );
    }

    public static function fromPreference(array $preference, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $preference['id'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: Payment::STATUS_PROCESSING,
            note: "MercadoPago Preference: {$preference['id']}",
            processedAt: new DateTime()
        );
    }
}
