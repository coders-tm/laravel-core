<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Models\Payment;

class PayPalPayment extends AbstractPayment
{
    public static function fromCapture(array $capture, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        $transactionId = $capture['purchase_units'][0]['payments']['captures'][0]['id'] ?? $capture['id'];

        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $transactionId,
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: $capture['status'] === 'COMPLETED' ? Payment::STATUS_COMPLETED : Payment::STATUS_FAILED,
            note: "PayPal Order: {$capture['id']}",
            processedAt: new DateTime()
        );
    }

    public static function fromOrder(array $order, int $paymentMethodId, float $amount, ?string $currency = null): self
    {
        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $order['id'],
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: Payment::STATUS_PENDING,
            note: "PayPal Order Created: {$order['id']}",
            processedAt: new DateTime()
        );
    }
}
