<?php

namespace Coderstm\Payment\Mappers;

use Coderstm\Contracts\PaymentInterface;
use Coderstm\Models\PaymentMethod;
use DateTime;

abstract class AbstractPayment implements PaymentInterface
{
    protected PaymentMethod $paymentMethod;

    protected string $transactionId;

    protected float $amount;

    protected string $currency;

    protected string $status;

    protected ?string $note;

    protected ?DateTime $processedAt;

    protected array $metadata;

    abstract protected function extractMetadata($response): array;

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getPaymentMethodId(): int
    {
        return $this->paymentMethod->id;
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

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toString(): string
    {
        return $this->metadata['payment_method'] ?? 'Unknown';
    }

    public function toArray(): array
    {
        return ['payment_method_id' => $this->getPaymentMethodId(), 'transaction_id' => $this->getTransactionId(), 'status' => $this->getStatus(), 'note' => $this->getNote(), 'processed_at' => $this->getProcessedAt(), 'metadata' => $this->getMetadata() + ['gateway_currency' => $this->getCurrency(), 'gateway_amount' => $this->getAmount()]];
    }
}
