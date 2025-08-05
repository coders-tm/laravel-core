<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Models\Payment;

class RazorpayPayment extends AbstractPayment
{
    public static function fromPayment(array $payment, int $paymentMethodId, float $amount, string $currency = 'INR'): self
    {
        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $payment['id'],
            amount: $amount,
            currency: $currency,
            status: $payment['status'] === 'captured' ? Payment::STATUS_COMPLETED : Payment::STATUS_FAILED,
            note: "Razorpay Payment: {$payment['id']}",
            processedAt: new DateTime()
        );
    }

    public static function fromOrder(array $order, int $paymentMethodId, float $amount, string $currency = 'INR'): self
    {
        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $order['id'],
            amount: $amount,
            currency: $currency,
            status: Payment::STATUS_PENDING,
            note: "Razorpay Order: {$order['id']}",
            processedAt: new DateTime()
        );
    }
}
