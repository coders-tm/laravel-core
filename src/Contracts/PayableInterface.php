<?php

namespace Coderstm\Contracts;

interface PayableInterface
{
    public function getGrandTotal(): float;

    public function getCurrency(): string;

    public function getGatewayAmount(): float;

    public function getTaxTotal(): float;

    public function getShippingTotal(): float;

    public function getCustomerEmail(): ?string;

    public function getCustomerFirstName(): ?string;

    public function getCustomerLastName(): ?string;

    public function getCustomerName(): ?string;

    public function getCustomerPhone(): ?string;

    public function getBillingAddress(): ?array;

    public function getShippingAddress(): ?array;

    public function getReferenceId(): string;

    public function getLineItems(): array;

    public function getMetadata(): array;

    public function getSource();

    public function getSourceId(): ?string;

    public function getDescription(): string;

    public function isCheckout(): bool;

    public function isOrder(): bool;
}
