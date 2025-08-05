<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Models\Payment;

class StripePayment extends AbstractPayment
{
    public static function fromIntent(array $intent, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        // Map Stripe statuses to our payment statuses
        $status = match ($intent['status']) {
            'succeeded', 'requires_capture' => Payment::STATUS_COMPLETED,
            'processing' => Payment::STATUS_PROCESSING,
            'requires_action', 'requires_source_action' => Payment::STATUS_PROCESSING,
            'requires_payment_method', 'requires_confirmation' => Payment::STATUS_FAILED,
            'canceled' => Payment::STATUS_CANCELLED,
            default => Payment::STATUS_FAILED,
        };

        $note = "Stripe Payment Intent: {$intent['id']} (Status: {$intent['status']})";
        if (isset($intent['next_action']['type'])) {
            $note .= " - Next action: {$intent['next_action']['type']}";
        }

        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $intent['id'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: $status,
            note: $note,
            processedAt: new DateTime()
        );
    }

    public static function fromCharge(array $charge, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $charge['id'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: $charge['status'] === 'succeeded' ? Payment::STATUS_COMPLETED : Payment::STATUS_FAILED,
            note: "Stripe Charge: {$charge['id']}",
            processedAt: new DateTime()
        );
    }
}
