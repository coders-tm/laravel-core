<?php

namespace Coderstm\Payment\Mappers;

use DateTime;
use Coderstm\Contracts\PaymentInterface;
use Coderstm\Models\Payment;

abstract class AbstractPayment implements PaymentInterface
{
    protected int $paymentMethodId;
    protected string $transactionId;
    protected float $amount;
    protected string $currency;
    protected string $status;
    protected ?string $note;
    protected ?DateTime $processedAt;

    public function __construct(
        int $paymentMethodId,
        string $transactionId,
        float $amount,
        ?string $currency = null,
        string $status = Payment::STATUS_COMPLETED,
        ?string $note = null,
        ?DateTime $processedAt = null
    ) {
        $this->paymentMethodId = $paymentMethodId;
        $this->transactionId = $transactionId;
        $this->amount = $amount;
        $this->currency = strtoupper($currency ?? config('app.currency', 'USD'));
        $this->status = $status;
        $this->note = $note;
        $this->processedAt = $processedAt ?? new DateTime();
    }

    public function getPaymentMethodId(): int
    {
        return $this->paymentMethodId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getProcessedAt(): ?DateTime
    {
        return $this->processedAt;
    }

    public function toArray(): array
    {
        return [
            'payment_method_id' => $this->getPaymentMethodId(),
            'transaction_id' => $this->getTransactionId(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'status' => $this->getStatus(),
            'note' => $this->getNote(),
            'processed_at' => $this->getProcessedAt(),
        ];
    }
}
