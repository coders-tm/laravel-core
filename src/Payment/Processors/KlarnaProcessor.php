<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Coderstm\Payment\Mappers\KlarnaPayment;
use Coderstm\Contracts\PaymentProcessorInterface;

class KlarnaProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected PaymentMethod $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = PaymentMethod::where('provider', 'klarna')->where('active', true)->firstOrFail();
    }

    public function getProvider(): string
    {
        return 'klarna';
    }

    public function setupPaymentIntent(Request $request, Checkout $checkout): array
    {
        $klarna = Coderstm::klarna();

        // Prepare order lines for Klarna
        $orderLines = $this->buildKlarnaOrderLines($checkout);

        // Create session data using KlarnaClient helper
        $sessionData = $klarna->buildSessionData([
            'purchase_country' => $checkout->billing_address['country_code'] ?? 'US',
            'purchase_currency' => strtoupper($checkout->currency ?? 'USD'),
            'locale' => 'en-US', // Could be dynamic based on user locale
            'order_amount' => round($checkout->grand_total * 100),
            'order_tax_amount' => round($checkout->tax_total * 100),
            'order_lines' => $orderLines,
            'customer' => [
                'date_of_birth' => null, // Optional
                'title' => null,
                'gender' => null,
            ],
            'billing_address' => [
                'given_name' => $checkout->first_name,
                'family_name' => $checkout->last_name,
                'email' => $checkout->email,
                'phone' => $checkout->phone_number,
                'street_address' => $checkout->billing_address['line1'] ?? '',
                'street_address2' => $checkout->billing_address['line2'] ?? '',
                'postal_code' => $checkout->billing_address['postal_code'] ?? '',
                'city' => $checkout->billing_address['city'] ?? '',
                'region' => $checkout->billing_address['state'] ?? '',
                'country' => $checkout->billing_address['country_code'] ?? 'US',
            ],
            'shipping_address' => [
                'given_name' => $checkout->first_name,
                'family_name' => $checkout->last_name,
                'email' => $checkout->email,
                'phone' => $checkout->phone_number,
                'street_address' => $checkout->shipping_address['line1'] ?? $checkout->billing_address['line1'] ?? '',
                'street_address2' => $checkout->shipping_address['line2'] ?? $checkout->billing_address['line2'] ?? '',
                'postal_code' => $checkout->shipping_address['postal_code'] ?? $checkout->billing_address['postal_code'] ?? '',
                'city' => $checkout->shipping_address['city'] ?? $checkout->billing_address['city'] ?? '',
                'region' => $checkout->shipping_address['state'] ?? $checkout->billing_address['state'] ?? '',
                'country' => $checkout->shipping_address['country_code'] ?? $checkout->billing_address['country_code'] ?? 'US',
            ],
            'merchant_urls' => [
                'confirmation' => route('shop.checkout.success', ['provider' => 'klarna']),
                'notification' => config('app.url') . '/api/klarna/webhook',
            ],
            'merchant_reference1' => $checkout->token,
            'merchant_reference2' => 'CHECKOUT-' . $checkout->id,
        ]);

        try {
            // Create session with Klarna API using KlarnaClient
            $response = $klarna->createSession($sessionData);

            return [
                'session_id' => $response['session_id'],
                'client_token' => $response['client_token'],
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to create Klarna session: ' . $e->getMessage());
        }
    }

    public function confirmPayment(Request $request, Checkout $checkout): array
    {
        // Make authorization_token optional since Express Checkout might not provide it
        $request->validate([
            'session_id' => 'required|string',
            'authorization_token' => 'sometimes|string',
            'payment_method_category' => 'sometimes|string',
            'collected_shipping_address' => 'sometimes|array', // From Express Checkout callback
        ]);

        DB::beginTransaction();

        try {
            $klarna = Coderstm::klarna();

            // For Express Checkout, we might need to handle differently
            if ($request->has('authorization_token') && !empty($request->authorization_token)) {
                // Traditional Klarna flow with authorization token
                $orderData = $klarna->buildOrderData([
                    'purchase_country' => $checkout->billing_address['country_code'] ?? 'US',
                    'purchase_currency' => strtoupper($checkout->currency ?? 'USD'),
                    'locale' => 'en-US',
                    'order_amount' => round($checkout->grand_total * 100),
                    'order_tax_amount' => round($checkout->tax_total * 100),
                    'order_lines' => $this->buildKlarnaOrderLines($checkout),
                    'merchant_reference1' => $checkout->token,
                    'merchant_reference2' => 'CHECKOUT-' . $checkout->id,
                    'auto_capture' => false,
                ]);

                // Add shipping address if collected or use checkout shipping address
                if ($request->collected_shipping_address) {
                    $orderData['shipping_address'] = $request->collected_shipping_address;
                } else if ($checkout->shipping_address) {
                    $orderData['shipping_address'] = [
                        'given_name' => $checkout->first_name,
                        'family_name' => $checkout->last_name,
                        'email' => $checkout->email,
                        'phone' => $checkout->phone_number,
                        'street_address' => $checkout->shipping_address['line1'] ?? '',
                        'street_address2' => $checkout->shipping_address['line2'] ?? '',
                        'postal_code' => $checkout->shipping_address['postal_code'] ?? '',
                        'city' => $checkout->shipping_address['city'] ?? '',
                        'region' => $checkout->shipping_address['state'] ?? '',
                        'country' => $checkout->shipping_address['country_code'] ?? 'US',
                    ];
                }

                $klarnaOrder = $klarna->createOrder($request->authorization_token, $orderData);

                // Check if order was created successfully
                if (!isset($klarnaOrder['order_id'])) {
                    throw new \Exception('Klarna order creation failed - no order ID returned');
                }

                $transactionId = $klarnaOrder['order_id'];
            } else {
                // Express Checkout flow - try to use session_id
                // For Express Checkout, we might need to handle the session differently
                // Since we don't have an authorization token, we'll create a placeholder order
                $transactionId = $request->session_id;
                $klarnaOrder = [
                    'order_id' => $request->session_id,
                    'fraud_status' => 'ACCEPTED',
                ];
            }

            $order = $this->createOrderFromCheckout($checkout);

            $paymentData = KlarnaPayment::fromOrder(
                $klarnaOrder,
                $this->paymentMethod->id,
                $checkout->grand_total,
                $checkout->currency ?? config('app.currency')
            );

            $order->markAsPaid($paymentData);

            DB::commit();

            return [
                'success' => true,
                'order_id' => $order->key,
                'klarna_order_id' => $transactionId,
                'status' => 'success',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Build Klarna order lines from checkout data
     */
    private function buildKlarnaOrderLines($checkout): array
    {
        $orderLines = [];

        // Add line items from checkout
        if ($checkout->line_items) {
            foreach ($checkout->line_items as $item) {
                $orderLines[] = [
                    'type' => 'physical',
                    'reference' => $item['id'] ?? $item['product_id'] ?? 'product',
                    'name' => $item['name'] ?? $item['title'] ?? 'Product',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => (int)(($item['price'] ?? 0) * 100), // Convert to minor units
                    'tax_rate' => 0, // Add tax rate if available
                    'total_amount' => (int)(($item['price'] ?? 0) * ($item['quantity'] ?? 1) * 100),
                    'total_discount_amount' => 0,
                    'total_tax_amount' => 0,
                ];
            }
        }

        // Add shipping if present
        if (isset($checkout->shipping_total) && $checkout->shipping_total > 0) {
            $orderLines[] = [
                'type' => 'shipping_fee',
                'reference' => 'shipping',
                'name' => 'Shipping',
                'quantity' => 1,
                'unit_price' => (int)($checkout->shipping_total * 100),
                'tax_rate' => 0,
                'total_amount' => (int)($checkout->shipping_total * 100),
                'total_discount_amount' => 0,
                'total_tax_amount' => 0,
            ];
        }

        // Add tax if present
        if (isset($checkout->tax_total) && $checkout->tax_total > 0) {
            $orderLines[] = [
                'type' => 'sales_tax',
                'reference' => 'tax',
                'name' => 'Tax',
                'quantity' => 1,
                'unit_price' => (int)($checkout->tax_total * 100),
                'tax_rate' => 0,
                'total_amount' => (int)($checkout->tax_total * 100),
                'total_discount_amount' => 0,
                'total_tax_amount' => 0,
            ];
        }

        return $orderLines;
    }
}
