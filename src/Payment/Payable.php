<?php

namespace Coderstm\Payment;

use Coderstm\Contracts\PayableInterface;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\Shop\ExchangeRate;
use Coderstm\Models\Shop\Order;

class Payable implements PayableInterface
{
    public function __construct(protected float $grandTotal, protected float $taxTotal = 0, protected float $shippingTotal = 0, protected ?string $customerEmail = null, protected ?string $customerFirstName = null, protected ?string $customerLastName = null, protected ?string $customerName = null, protected ?string $customerPhone = null, protected ?array $billingAddress = null, protected ?array $shippingAddress = null, protected string $referenceId = '', protected mixed $lineItems = null, protected string $type = 'checkout', protected mixed $source = null, protected array $currencies = []) {}

    public static function make(array $data): static
    {
        return new static(grandTotal: (float) ($data['grand_total'] ?? 0), taxTotal: (float) ($data['tax_total'] ?? 0), shippingTotal: (float) ($data['shipping_total'] ?? 0), customerEmail: $data['customer_email'] ?? null, customerFirstName: $data['customer_first_name'] ?? null, customerLastName: $data['customer_last_name'] ?? null, customerName: $data['customer_name'] ?? null, customerPhone: $data['customer_phone'] ?? null, billingAddress: $data['billing_address'] ?? null, shippingAddress: $data['shipping_address'] ?? null, referenceId: $data['reference_id'] ?? '', lineItems: $data['line_items'] ?? null, type: $data['type'] ?? 'checkout', source: $data['source'] ?? null);
    }

    public static function fromCheckout(Checkout $checkout, ?float $grandTotal = null): static
    {
        return new static(grandTotal: $grandTotal ?? $checkout->grand_total, taxTotal: $checkout->tax_total ?? 0, shippingTotal: $checkout->shipping_total ?? 0, customerEmail: $checkout->email, customerFirstName: $checkout->first_name, customerLastName: $checkout->last_name, customerName: $checkout->name, customerPhone: $checkout->phone_number, billingAddress: $checkout->billing_address, shippingAddress: $checkout->shipping_address, referenceId: $checkout->token, lineItems: $checkout->line_items, type: 'checkout', source: $checkout);
    }

    public static function fromOrder(Order $order): static
    {
        return new static(grandTotal: $order->grand_total, taxTotal: $order->tax_total ?? 0.0, shippingTotal: $order->shipping_total ?? 0, customerEmail: $order->contact?->email ?? $order->customer?->email, customerFirstName: $order->contact?->first_name ?? $order->customer?->first_name, customerLastName: $order->contact?->last_name ?? $order->customer?->last_name, customerName: $order->contact?->name ?? $order->customer?->name, customerPhone: $order->contact?->phone_number ?? $order->customer?->phone_number, billingAddress: $order->billing_address, shippingAddress: $order->shipping_address, referenceId: $order->key, lineItems: $order->line_items, type: 'order', source: $order);
    }

    public function setCurrencies(array $currencies): void
    {
        $this->currencies = $currencies;
    }

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

    public function getCurrency(): string
    {
        $billingAddress = $this->billingAddress;
        $currency = null;
        $baseCurrency = ExchangeRate::getBaseCurrency();
        if ($billingAddress) {
            $countryCode = $billingAddress['country_code'] ?? '';
            $country = $billingAddress['country'] ?? '';
            if ($countryCode) {
                $currency = ExchangeRate::getCurrencyFromCountryCode($countryCode);
            }
            if ((! $currency || $currency === $baseCurrency) && $country) {
                $detectedCurrency = ExchangeRate::getCurrencyFromCountry($country);
                if ($detectedCurrency !== $baseCurrency) {
                    $currency = $detectedCurrency;
                }
            }
        }
        if (! $currency || $currency === $baseCurrency) {
            return $baseCurrency;
        }
        if (empty($this->currencies) || in_array($currency, $this->currencies)) {
            if (ExchangeRate::where('currency', $currency)->exists()) {
                return $currency;
            }
        }

        return $baseCurrency;
    }

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
        $items = $this->toArrayFormat($this->lineItems);

        return array_map([$this, 'toArrayFormat'], $items);
    }

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
        return ['checkout_token' => $this->referenceId, 'customer_email' => $this->customerEmail, 'type' => $this->type];
    }

    public function getDescription(): string
    {
        if ($this->isCheckout()) {
            return 'Payment for checkout';
        }
        $orderId = $this->source?->id ?? $this->referenceId;

        return "Payment for order #{$orderId}";
    }

    public function isCheckout(): bool
    {
        return $this->type === 'checkout';
    }

    public function isOrder(): bool
    {
        return $this->type === 'order';
    }

    public function toArray(): array
    {
        return ['grand_total' => $this->grandTotal, 'line_items' => $this->lineItems, 'tax_total' => $this->taxTotal, 'shipping_total' => $this->shippingTotal, 'customer_email' => $this->customerEmail, 'customer_first_name' => $this->customerFirstName, 'customer_last_name' => $this->customerLastName, 'customer_phone' => $this->customerPhone, 'billing_address' => $this->billingAddress, 'shipping_address' => $this->shippingAddress, 'reference_id' => $this->referenceId, 'type' => $this->type];
    }
}
