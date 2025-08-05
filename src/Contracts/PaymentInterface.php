<?php

namespace Coderstm\Contracts;

interface PaymentInterface
{
    /**
     * Get the payment method ID
     */
    public function getPaymentMethodId(): int;

    /**
     * Get the transaction ID
     */
    public function getTransactionId(): string;

    /**
     * Get the payment amount
     */
    public function getAmount(): float;

    /**
     * Get the payment currency
     */
    public function getCurrency(): string;

    /**
     * Get the payment status
     */
    public function getStatus(): string;

    /**
     * Get additional payment note
     */
    public function getNote(): ?string;

    /**
     * Get the processed timestamp
     */
    public function getProcessedAt(): ?\DateTime;

    /**
     * Convert payment data to array format
     */
    public function toArray(): array;
}
