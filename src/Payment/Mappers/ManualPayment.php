<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Models\Payment;
use Illuminate\Support\Str;

class ManualPayment extends AbstractPayment
{
    public static function create(
        int $paymentMethodId,
        float $amount,
        ?string $currency = null,
        ?string $referenceNumber = null,
        ?string $note = null
    ): self {
        $transactionId = 'MANUAL_' . Str::upper(Str::random(10));

        return new self(
            paymentMethodId: $paymentMethodId,
            transactionId: $transactionId,
            amount: $amount,
            currency: $currency ?? config('app.currency', 'USD'),
            status: Payment::STATUS_PENDING,
            note: $note,
            processedAt: new DateTime()
        );
    }

    public static function fromReference(
        string $referenceNumber,
        int $paymentMethodId,
        float $amount,
        ?string $currency = null,
        ?string $note = null
    ): self {
        return self::create($paymentMethodId, $amount, $currency, $referenceNumber, $note);
    }
}
