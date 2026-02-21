<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Coderstm\Contracts\PaymentProcessorInterface;
use Coderstm\Models\PaymentMethod;
use Coderstm\Payment\Mappers\KlarnaPayment;
use Coderstm\Payment\Payable;
use Coderstm\Payment\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KlarnaProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    private const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'SEK', 'DKK', 'NOK', 'PLN', 'CHF', 'AUD', 'CAD', 'NZD'];

    private const MINOR_UNITS_MULTIPLIER = 100;

    private const MAX_REFERENCE_LENGTH = 255;

    private const COUNTRY_LOCALES = ['US' => 'en-US', 'GB' => 'en-GB', 'DE' => 'de-DE', 'AT' => 'de-AT', 'NL' => 'nl-NL', 'BE' => 'nl-BE', 'CH' => 'de-CH', 'DK' => 'da-DK', 'FI' => 'fi-FI', 'NO' => 'nb-NO', 'SE' => 'sv-SE', 'PL' => 'pl-PL'];

    private const KLARNA_SUPPORTED_COUNTRIES = ['US', 'GB', 'DE', 'AT', 'NL', 'BE', 'CH', 'DK', 'FI', 'NO', 'SE', 'PL'];

    public function getProvider(): string
    {
        return PaymentMethod::KLARNA;
    }

    public function supportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function setupPaymentIntent(Request $request, Payable $payable): array
    {
        $this->validatePaymentRequirements($payable);
        $billingAddress = $payable->getBillingAddress();
        $shippingAddress = $payable->getShippingAddress() ?: $billingAddress;
        $sanitizedBillingAddress = $this->sanitizeAddress($billingAddress, 'billing');
        $sanitizedShippingAddress = $this->sanitizeAddress($shippingAddress, 'shipping');
        $klarna = Coderstm::klarna();
        $orderLines = $this->buildKlarnaOrderLines($payable);
        $this->validateOrderLinesTotal($orderLines, $payable->getGrandTotal());
        $sessionData = $this->buildSessionData($payable, $sanitizedBillingAddress, $sanitizedShippingAddress, $orderLines);
        try {
            $response = $klarna->createSession($sessionData);

            return ['session_id' => $response['session_id'], 'client_token' => $response['client_token'], 'amount' => $payable->getGrandTotal(), 'currency' => strtoupper(config('app.currency', 'USD'))];
        } catch (\Throwable $e) {
            $this->logSessionCreationFailure($sessionData, $orderLines, $payable, $e);
            throw new \Exception('Failed to create Klarna session: '.$e->getMessage());
        }
    }

    public function confirmPayment(Request $request, Payable $payable): PaymentResult
    {
        $request->validate(['session_id' => 'required|string', 'authorization_token' => 'sometimes|string', 'payment_method_category' => 'sometimes|string', 'collected_shipping_address' => 'sometimes|array']);
        $klarna = Coderstm::klarna();
        if ($request->filled('authorization_token')) {
            return $this->processTraditionalKlarnaFlow($request, $payable, $klarna);
        }

        return $this->processExpressCheckoutFlow($request, $payable);
    }

    private function processTraditionalKlarnaFlow(Request $request, Payable $payable, $klarna): PaymentResult
    {
        try {
            if ($request->filled('session_id')) {
                $session = $klarna->getSession($request->session_id);
                if (isset($session['order_id']) && ! empty($session['order_id'])) {
                    $klarnaOrder = ['order_id' => $session['order_id'], 'fraud_status' => 'ACCEPTED'];
                    $paymentData = new KlarnaPayment($klarnaOrder, $this->paymentMethod);

                    return PaymentResult::success(paymentData: $paymentData, transactionId: $session['order_id'], status: 'success');
                }
            }
            $orderData = $this->buildOrderData($payable, $request);
            $klarnaOrder = $klarna->createOrder($request->authorization_token, $orderData);
            if (! isset($klarnaOrder['order_id'])) {
                PaymentResult::failed('Klarna order creation failed - no order ID returned');
            }
            $paymentData = new KlarnaPayment($klarnaOrder, $this->paymentMethod);

            return PaymentResult::success(paymentData: $paymentData, transactionId: $klarnaOrder['order_id'], status: 'success');
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), '409 Conflict') && $request->filled('session_id')) {
                try {
                    sleep(1);
                    $session = $klarna->getSession($request->session_id);
                    if (isset($session['order_id']) && ! empty($session['order_id'])) {
                        $klarnaOrder = ['order_id' => $session['order_id'], 'fraud_status' => 'ACCEPTED'];
                        $paymentData = new KlarnaPayment($klarnaOrder, $this->paymentMethod);

                        return PaymentResult::success(paymentData: $paymentData, transactionId: $session['order_id'], status: 'success');
                    }
                } catch (\Throwable $sessionError) {
                }
            }
            if (str_contains($e->getMessage(), 'Duplicate merchant reference')) {
                PaymentResult::failed('This order has already been processed. Please check your order history or contact support.');
            }
            $errorMessage = $this->parseKlarnaError($e->getMessage());
            PaymentResult::failed($errorMessage);
        }
    }

    private function processExpressCheckoutFlow(Request $request, Payable $payable): PaymentResult
    {
        $klarnaOrder = ['order_id' => $request->session_id, 'fraud_status' => 'ACCEPTED'];
        $paymentData = new KlarnaPayment($klarnaOrder, $this->paymentMethod);

        return PaymentResult::success(paymentData: $paymentData, transactionId: $request->session_id, status: 'success');
    }

    private function buildOrderData(Payable $payable, Request $request): array
    {
        $billingAddress = $payable->getBillingAddress();
        $shippingAddress = $payable->getShippingAddress() ?: $billingAddress;
        $orderData = ['purchase_country' => $billingAddress['country_code'] ?? 'US', 'purchase_currency' => strtoupper(config('app.currency', 'USD')), 'locale' => 'en-US', 'order_amount' => $this->convertToMinorUnits($payable->getGrandTotal()), 'order_tax_amount' => $this->convertToMinorUnits($payable->getTaxTotal()), 'order_lines' => $this->buildKlarnaOrderLines($payable), 'merchant_reference1' => $payable->getReferenceId(), 'auto_capture' => false];
        if ($request->filled('collected_shipping_address')) {
            $orderData['shipping_address'] = $request->collected_shipping_address;
        } elseif ($shippingAddress) {
            $orderData['shipping_address'] = ['given_name' => $payable->getCustomerFirstName(), 'family_name' => $payable->getCustomerLastName(), 'email' => $payable->getCustomerEmail(), 'phone' => $payable->getCustomerPhone(), 'street_address' => $shippingAddress['line1'] ?? '', 'street_address2' => $shippingAddress['line2'] ?? '', 'postal_code' => $shippingAddress['postal_code'] ?? '', 'city' => $shippingAddress['city'] ?? '', 'region' => $shippingAddress['state'] ?? '', 'country' => $shippingAddress['country_code'] ?? 'US'];
        }

        return $orderData;
    }

    private function validatePaymentRequirements(Payable $payable): void
    {
        $this->validateCurrency($payable);
        $this->validateCustomerData($payable);
        $this->validateLineItems($payable->getLineItems());
    }

    private function validateLineItems(array $lineItems): void
    {
        if (empty($lineItems)) {
            throw new \Exception('At least one line item is required for Klarna payments');
        }
        foreach ($lineItems as $item) {
            if (empty($item['name']) && empty($item['title'])) {
                throw new \Exception('Line item name is required for Klarna payments');
            }
            if (! isset($item['price']) || ! is_numeric($item['price'])) {
                throw new \Exception('Line item price is required and must be numeric for Klarna payments');
            }
            if ($item['price'] <= 0) {
                throw new \Exception('Line item price must be greater than zero for Klarna payments');
            }
        }
    }

    private function validateOrderLinesTotal(array $orderLines, float $expectedTotal): void
    {
        if (empty($orderLines)) {
            throw new \Exception('At least one valid order line is required for Klarna payments');
        }
        $calculatedTotal = array_sum(array_column($orderLines, 'total_amount'));
        $expectedTotalCents = round($expectedTotal * 100);
        if (abs($calculatedTotal - $expectedTotalCents) > 1) {
            throw new \Exception("Order lines total ({$calculatedTotal}) does not match expected total ({$expectedTotalCents})");
        }
    }

    private function buildSessionData(Payable $payable, array $billingAddress, array $shippingAddress, array $orderLines): array
    {
        $currency = strtoupper(config('app.currency', 'USD'));
        $expectedTotal = round($payable->getGrandTotal() * 100);

        return ['purchase_country' => $billingAddress['country'], 'purchase_currency' => $currency, 'locale' => $this->getLocaleForCountry($billingAddress['country']), 'order_amount' => $expectedTotal, 'order_tax_amount' => round($payable->getTaxTotal() * 100), 'order_lines' => $orderLines, 'customer' => ['date_of_birth' => null, 'title' => null, 'gender' => null], 'billing_address' => $this->buildAddressData($payable, $billingAddress), 'shipping_address' => $this->buildAddressData($payable, $shippingAddress), 'merchant_urls' => ['confirmation' => $this->getSuccessUrl(), 'notification' => $this->getWebhookUrl()], 'merchant_reference1' => $payable->getReferenceId(), 'merchant_reference2' => $payable->isCheckout() ? 'CHECKOUT-'.$payable->getSourceId() : 'ORDER-'.$payable->getSourceId()];
    }

    private function buildAddressData(Payable $payable, array $address): array
    {
        return ['given_name' => $this->sanitizeName($payable->getCustomerFirstName()), 'family_name' => $this->sanitizeName($payable->getCustomerLastName()), 'email' => $this->sanitizeEmail($payable->getCustomerEmail()), 'phone' => $this->sanitizePhone($payable->getCustomerPhone(), $address['country']), 'street_address' => $address['street_address'], 'street_address2' => $address['street_address2'], 'postal_code' => $address['postal_code'], 'city' => $address['city'], 'region' => $address['region'], 'country' => $address['country']];
    }

    private function logSessionCreationFailure(array $sessionData, array $orderLines, Payable $payable, \Throwable $e): void
    {
        Log::error('Klarna session creation failed', ['session_data' => $sessionData, 'order_lines' => $orderLines, 'payable_data' => ['grand_total' => $payable->getGrandTotal(), 'tax_total' => $payable->getTaxTotal(), 'shipping_total' => $payable->getShippingTotal(), 'currency' => strtoupper(config('app.currency', 'USD')), 'line_items_count' => count($payable->getLineItems())], 'error' => $e->getMessage()]);
    }

    private function buildKlarnaOrderLines(Payable $payable): array
    {
        $orderLines = [];
        $orderLines = array_merge($orderLines, $this->buildLineItemOrderLines($payable));
        $orderLines = array_merge($orderLines, $this->buildShippingOrderLines($payable));
        $orderLines = array_merge($orderLines, $this->buildTaxOrderLines($payable));

        return $orderLines;
    }

    private function buildLineItemOrderLines(Payable $payable): array
    {
        $orderLines = [];
        $lineItems = $payable->getLineItems();
        foreach ($lineItems as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $price = (float) $item['price'];
            if ($price <= 0 || $quantity <= 0) {
                continue;
            }
            $orderLines[] = ['type' => 'physical', 'reference' => $this->getLineItemReference($item), 'name' => $this->getLineItemName($item), 'quantity' => $quantity, 'unit_price' => $this->convertToMinorUnits($price), 'tax_rate' => 0, 'total_amount' => $this->convertToMinorUnits($price * $quantity), 'total_discount_amount' => 0, 'total_tax_amount' => 0];
        }

        return $orderLines;
    }

    private function buildShippingOrderLines(Payable $payable): array
    {
        $shippingTotal = $payable->getShippingTotal();
        if ($shippingTotal <= 0) {
            return [];
        }

        return [['type' => 'shipping_fee', 'reference' => 'shipping', 'name' => 'Shipping', 'quantity' => 1, 'unit_price' => $this->convertToMinorUnits($shippingTotal), 'tax_rate' => 0, 'total_amount' => $this->convertToMinorUnits($shippingTotal), 'total_discount_amount' => 0, 'total_tax_amount' => 0]];
    }

    private function buildTaxOrderLines(Payable $payable): array
    {
        $taxTotal = $payable->getTaxTotal();
        if ($taxTotal <= 0) {
            return [];
        }

        return [['type' => 'sales_tax', 'reference' => 'tax', 'name' => 'Tax', 'quantity' => 1, 'unit_price' => $this->convertToMinorUnits($taxTotal), 'tax_rate' => 0, 'total_amount' => $this->convertToMinorUnits($taxTotal), 'total_discount_amount' => 0, 'total_tax_amount' => 0]];
    }

    private function getLineItemReference(array $item): string
    {
        $reference = (string) ($item['id'] ?? $item['product_id'] ?? 'product');

        return substr($reference, 0, self::MAX_REFERENCE_LENGTH);
    }

    private function getLineItemName(array $item): string
    {
        $name = (string) ($item['name'] ?? $item['title']);

        return substr($name, 0, self::MAX_REFERENCE_LENGTH);
    }

    private function convertToMinorUnits(float $amount): int
    {
        return (int) ($amount * self::MINOR_UNITS_MULTIPLIER);
    }

    private function validateCustomerData(Payable $payable): void
    {
        $this->sanitizeName($payable->getCustomerFirstName());
        $this->sanitizeName($payable->getCustomerLastName());
        $this->sanitizeEmail($payable->getCustomerEmail());
    }

    private function sanitizeAddress(array $address, string $type): array
    {
        return ['street_address' => $this->sanitizeStreetAddress($address['line1'] ?? ''), 'street_address2' => $this->sanitizeOptionalStreetAddress($address['line2'] ?? ''), 'postal_code' => $this->sanitizePostalCode($address['postal_code'] ?? '', $address['country_code'] ?? 'US'), 'city' => $this->sanitizeCity($address['city'] ?? ''), 'region' => $this->sanitizeRegion($address['state'] ?? '', $address['country_code'] ?? 'US'), 'country' => $this->sanitizeCountry($address['country_code'] ?? 'US')];
    }

    private function sanitizeName(?string $name): string
    {
        if (! $name || strlen(trim($name)) === 0) {
            throw new \Exception('Customer name is required for Klarna payments');
        }
        $sanitized = preg_replace('/[^\\p{L}\\s\\-\'\\.]/u', '', trim($name));
        if (! $sanitized || strlen($sanitized) === 0) {
            throw new \Exception('Customer name contains invalid characters');
        }

        return mb_substr($sanitized, 0, 50);
    }

    private function sanitizeEmail(?string $email): string
    {
        if (! $email || strlen(trim($email)) === 0) {
            throw new \Exception('Customer email is required for Klarna payments');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid customer email format for Klarna payments');
        }

        return mb_substr($email, 0, 100);
    }

    private function sanitizePhone(?string $phone, string $country): string
    {
        if (! $phone) {
            return '';
        }
        $sanitized = preg_replace('/[^\\d\\+\\s\\-\\(\\)]/', '', $phone);
        if (strlen(preg_replace('/[^\\d]/', '', $sanitized)) < 7) {
            return '';
        }

        return mb_substr($sanitized, 0, 20);
    }

    private function sanitizeStreetAddress(?string $address): string
    {
        if (! $address || strlen(trim($address)) === 0) {
            throw new \Exception('Street address is required for Klarna payments');
        }
        $sanitized = preg_replace('/[^\\p{L}\\p{N}\\s\\-\\.,#\\/]/u', '', trim($address));
        if (! $sanitized || strlen($sanitized) === 0) {
            throw new \Exception('Street address contains invalid characters');
        }

        return mb_substr($sanitized, 0, 100);
    }

    private function sanitizeOptionalStreetAddress(?string $address): string
    {
        if (! $address || strlen(trim($address)) === 0) {
            return '';
        }
        $sanitized = preg_replace('/[^\\p{L}\\p{N}\\s\\-\\.,#\\/]/u', '', trim($address));

        return mb_substr($sanitized ?: '', 0, 100);
    }

    private function sanitizePostalCode(?string $postalCode, string $country): string
    {
        if (! $postalCode || strlen(trim($postalCode)) === 0) {
            throw new \Exception('Postal code is required for Klarna payments');
        }
        if (preg_match('/[a-z]{3,}/i', $postalCode) && ! preg_match('/^[a-z0-9\\s\\-]{3,10}$/i', $postalCode)) {
            throw new \Exception('Invalid postal code format. Postal code cannot contain names or invalid characters');
        }
        $sanitized = preg_replace('/[^\\p{L}\\p{N}\\s\\-]/u', '', trim($postalCode));
        if (! $sanitized || strlen($sanitized) === 0) {
            throw new \Exception('Postal code contains invalid characters');
        }

        return mb_substr($sanitized, 0, 10);
    }

    private function sanitizeCity(?string $city): string
    {
        if (! $city || strlen(trim($city)) === 0) {
            throw new \Exception('City is required for Klarna payments');
        }
        if (preg_match('/^\\d+$/', trim($city))) {
            throw new \Exception('City cannot be numeric');
        }
        $sanitized = preg_replace('/[^\\p{L}\\s\\-\'\\.]/u', '', trim($city));
        if (! $sanitized || strlen($sanitized) === 0) {
            throw new \Exception('City contains invalid characters');
        }

        return mb_substr($sanitized, 0, 50);
    }

    private function sanitizeRegion(?string $region, string $country): string
    {
        if (! $region || strlen(trim($region)) === 0) {
            throw new \Exception('State/Region is required for Klarna payments');
        }
        $sanitized = preg_replace('/[^\\p{L}\\s\\-]/u', '', trim($region));
        if (! $sanitized || strlen($sanitized) === 0) {
            throw new \Exception('State/Region contains invalid characters');
        }

        return mb_substr($sanitized, 0, 50);
    }

    private function sanitizeCountry(?string $country): string
    {
        if (! $country) {
            return 'US';
        }
        $country = strtoupper(trim($country));

        return in_array($country, self::KLARNA_SUPPORTED_COUNTRIES) ? $country : 'US';
    }

    private function getLocaleForCountry(string $country): string
    {
        return self::COUNTRY_LOCALES[$country] ?? 'en-US';
    }

    private function parseKlarnaError(string $errorMessage): string
    {
        if (str_contains($errorMessage, '409 Conflict')) {
            if (str_contains($errorMessage, 'Not matching fields')) {
                return 'Payment failed: Order details do not match the authorized session. Please try again.';
            }

            return 'This payment authorization has already been processed or has expired. Please restart the payment process.';
        }
        if (str_contains($errorMessage, '400 Bad Request')) {
            if (preg_match('/"error_messages"\\s*:\\s*\\[\\s*"([^"]+)"/', $errorMessage, $matches)) {
                return 'Payment failed: '.$matches[1];
            }

            return 'Invalid payment data provided. Please check your information and try again.';
        }
        if (str_contains($errorMessage, '401 Unauthorized')) {
            return 'Payment authorization failed. Please contact support.';
        }
        if (str_contains($errorMessage, '403 Forbidden')) {
            return 'Payment not allowed. Please contact support.';
        }
        if (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'timed out')) {
            return 'Payment processing timed out. Please try again.';
        }
        $cleanError = preg_replace('/Client error:.*resulted in/s', 'Payment failed:', $errorMessage);
        $cleanError = preg_replace('/\\(truncated\\.\\.\\.\\).*$/', '', $cleanError);

        return trim($cleanError) ?: 'Payment processing failed. Please try again or contact support.';
    }
}
