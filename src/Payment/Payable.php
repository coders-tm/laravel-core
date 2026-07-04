<?php

namespace Coderstm\Payment;

use Coderstm\Contracts\PayableInterface;
use Coderstm\Models\Shop\ExchangeRate;
use Coderstm\Models\Shop\Order;

class Payable implements PayableInterface
{
    public function __construct(
        protected float $grandTotal,
        protected float $taxTotal = 0,
        protected float $shippingTotal = 0,
        protected ?string $customerEmail = null,
        protected ?string $customerFirstName = null,
        protected ?string $customerLastName = null,
        protected ?string $customerName = null,
        protected ?string $customerPhone = null,
        protected ?array $billingAddress = null,
        protected ?array $shippingAddress = null,
        protected string $referenceId = '',
        protected mixed $lineItems = null,
        protected string $type = 'checkout',
        protected mixed $source = null,
        protected array $currencies = [],
        protected ?string $successCallbackUrl = null,
        protected ?string $cancelCallbackUrl = null,
    ) {}

    /**
     * Create a Payable instance from array data
     */
    public static function make(array $data): static
    {
        return new static(
            grandTotal: (float) ($data['grand_total'] ?? 0),
            taxTotal: (float) ($data['tax_total'] ?? 0),
            shippingTotal: (float) ($data['shipping_total'] ?? 0),
            customerEmail: $data['customer_email'] ?? null,
            customerFirstName: $data['customer_first_name'] ?? null,
            customerLastName: $data['customer_last_name'] ?? null,
            customerName: $data['customer_name'] ?? null,
            customerPhone: $data['customer_phone'] ?? null,
            billingAddress: $data['billing_address'] ?? null,
            shippingAddress: $data['shipping_address'] ?? null,
            referenceId: $data['reference_id'] ?? '',
            lineItems: $data['line_items'] ?? null,
            type: $data['type'] ?? 'checkout',
            source: $data['source'] ?? null,
            successCallbackUrl: $data['successCallbackUrl'] ?? $data['success_callback_url'] ?? null,
            cancelCallbackUrl: $data['cancelCallbackUrl'] ?? $data['cancel_callback_url'] ?? null,
        );
    }

    /**
     * Create a Payable instance from an Order model.
     *
     * @param  Order  $order
     */
    public static function fromOrder($order): static
    {
        return new static(
            grandTotal: $order->grand_total,
            taxTotal: $order->tax_total ?? 0.00,
            shippingTotal: $order->shipping_total ?? 0,
            customerEmail: $order->contact?->email ?? $order->customer?->email,
            customerFirstName: $order->contact?->first_name ?? $order->customer?->first_name,
            customerLastName: $order->contact?->last_name ?? $order->customer?->last_name,
            customerName: $order->contact?->name ?? $order->customer?->name,
            customerPhone: $order->contact?->phone_number ?? $order->customer?->phone_number,
            billingAddress: $order->billing_address,
            shippingAddress: $order->shipping_address,
            referenceId: $order->key,
            lineItems: $order->line_items,
            type: 'order',
            source: $order
        );
    }

    /**
     * Set supported currencies for this payable instance
     */
    public function setCurrencies(array $currencies): void
    {
        $this->currencies = $currencies;
    }

    public function setSuccessCallbackUrl(?string $url): static
    {
        $this->successCallbackUrl = $url;

        return $this;
    }

    public function setCancelCallbackUrl(?string $url): static
    {
        $this->cancelCallbackUrl = $url;

        return $this;
    }

    /**
     * Get the success callback URL with optional query params appended.
     * Returns null if no custom callback URL is set.
     */
    public function getSuccessCallbackUrl(array $params = []): ?string
    {
        if ($this->successCallbackUrl === null) {
            return null;
        }

        $url = $this->successCallbackUrl;

        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }

    /**
     * Get the cancel callback URL with optional query params appended.
     * Returns null if no custom callback URL is set.
     */
    public function getCancelCallbackUrl(array $params = []): ?string
    {
        if ($this->cancelCallbackUrl === null) {
            return null;
        }

        $url = $this->cancelCallbackUrl;

        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }

    // Getters
    public function getGrandTotal(): float
    {
        return $this->grandTotal;
    }

    public function getTaxTotal(): float
    {
        return $this->taxTotal;
    }

    public function getShippingTotal(): float
    {
        return $this->shippingTotal;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function getCustomerFirstName(): ?string
    {
        return $this->customerFirstName;
    }

    public function getCustomerLastName(): ?string
    {
        return $this->customerLastName;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function getBillingAddress(): ?array
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): ?array
    {
        return $this->shippingAddress;
    }

    /**
     * Get the currency for this payment based on billing address.
     *
     * Uses ISO3166 library to determine currency from country/country code.
     * Falls back to base currency if:
     * - No billing address is provided
     * - Currency is not supported by payment gateway
     * - Currency has no exchange rate in database
     *
     * Note: Currency will only be returned if:
     * 1. It's supported by the payment gateway (if currencies list is set)
     * 2. It has an exchange rate in the database (or is the base currency)
     */
    public function getCurrency(): string
    {
        $billingAddress = $this->billingAddress;
        $currency = null;
        $baseCurrency = ExchangeRate::getBaseCurrency();

        if ($billingAddress) {
            $countryCode = $billingAddress['country_code'] ?? '';
            $country = $billingAddress['country'] ?? '';

            // Try to get currency from country code first (more reliable)
            if ($countryCode) {
                $currency = ExchangeRate::getCurrencyFromCountryCode($countryCode);
            }

            // Fallback to country name if country code didn't work
            if ((! $currency || $currency === $baseCurrency) && $country) {
                $detectedCurrency = ExchangeRate::getCurrencyFromCountry($country);
                // Only use if it's different from base currency
                if ($detectedCurrency !== $baseCurrency) {
                    $currency = $detectedCurrency;
                }
            }
        }

        // If no currency detected or it's the base currency, just return base
        if (! $currency || $currency === $baseCurrency) {
            return $baseCurrency;
        }

        // Check if currency is valid:
        // 1. Must be supported by gateway (if currencies list is set)
        // 2. Must have an exchange rate in the database
        if (empty($this->currencies) || in_array($currency, $this->currencies)) {
            if (ExchangeRate::where('currency', $currency)->exists()) {
                return $currency;
            }
        }

        // Fallback to base currency if validation failed
        return $baseCurrency;
    }

    /**
     * Get the amount converted to the gateway currency.
     *
     * Converts the grand total from the base currency to the gateway currency.
     */
    public function getGatewayAmount(): float
    {
        $baseCurrency = ExchangeRate::getBaseCurrency();
        $gatewayCurrency = $this->getCurrency();

        if ($baseCurrency === $gatewayCurrency) {
            return $this->grandTotal;
        }

        return ExchangeRate::convertAmount($this->grandTotal, $baseCurrency, $gatewayCurrency);
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function getLineItems(): array
    {
        if (! $this->lineItems) {
            return [];
        }

        // Convert line items to array format
        $items = $this->toArrayFormat($this->lineItems);

        // Normalize each individual item to array format
        return array_map([$this, 'toArrayFormat'], $items);
    }

    /**
     * Convert any value (collection, object, or array) to array format
     */
    private function toArrayFormat(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return [];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): mixed
    {
        return $this->source;
    }

    public function getSourceId(): ?string
    {
        return $this->source?->id ?? null;
    }

    public function getMetadata(): array
    {
        return [
            'checkout_token' => $this->referenceId,
            'customer_email' => $this->customerEmail,
            'type' => $this->type,
        ];
    }

    public function getDescription(): string
    {
        $orderId = $this->source?->id ?? $this->referenceId;

        return match ($this->type) {
            'order' => "Payment for order #{$orderId}",
            'mandate' => 'Payment for mandate',
            default => 'Payment for checkout',
        };
    }

    public function isCheckout(): bool
    {
        return $this->type === 'checkout';
    }

    public function isOrder(): bool
    {
        return $this->type === 'order';
    }

    public function isMandate(): bool
    {
        return $this->type === 'mandate';
    }

    public function toArray(): array
    {
        return [
            'grand_total' => $this->grandTotal,
            'line_items' => $this->lineItems,
            'tax_total' => $this->taxTotal,
            'shipping_total' => $this->shippingTotal,
            'customer_email' => $this->customerEmail,
            'customer_first_name' => $this->customerFirstName,
            'customer_last_name' => $this->customerLastName,
            'customer_phone' => $this->customerPhone,
            'billing_address' => $this->billingAddress,
            'shipping_address' => $this->shippingAddress,
            'reference_id' => $this->referenceId,
            'type' => $this->type,
        ];
    }
}
