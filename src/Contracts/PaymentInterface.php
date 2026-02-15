<?php

namespace Coderstm\Contracts;

interface PaymentInterface
{
    public function getPaymentMethodId(): int;

    public function getTransactionId(): string;

    public function getAmount(): float;

    public function getCurrency(): string;

    public function getStatus(): string;

    public function getNote(): ?string;

    public function getProcessedAt(): ?\DateTime;

    public function toString(): string;

    public function toArray(): array;
}
