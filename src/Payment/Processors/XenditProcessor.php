<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Coderstm\Payment\Mappers\XenditPayment;
use Coderstm\Contracts\PaymentProcessorInterface;

class XenditProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected PaymentMethod $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = PaymentMethod::where('provider', 'xendit')->where('active', true)->firstOrFail();
    }

    public function getProvider(): string
    {
        return 'xendit';
    }

    public function handleSuccessCallback(Request $request): array
    {
        try {
            // Get state ID from query parameter
            $stateId = $request->query('state');

            if (!$stateId) {
                Log::error('Xendit success callback: Missing state parameter');
                return [
                    'success' => false,
                    'redirect_url' => '/user/shop/checkout',
                    'message' => 'Invalid payment session. Please try again.'
                ];
            }

            // Retrieve payment state from cache
            $paymentState = Cache::get('xendit_payment_states.' . $stateId);

            if (!$paymentState) {
                Log::error('Xendit success callback: Payment state not found', [
                    'state_id' => $stateId
                ]);
                return [
                    'success' => false,
                    'redirect_url' => '/user/shop/checkout',
                    'message' => 'Payment session expired. Please try again.'
                ];
            }

            // Find the checkout
            $checkout = Checkout::where('token', $paymentState['checkout_token'])->first();

            if (!$checkout) {
                Log::error('Xendit success callback: Checkout not found', [
                    'checkout_token' => $paymentState['checkout_token'],
                    'state_id' => $stateId
                ]);
                return [
                    'success' => false,
                    'redirect_url' => '/user/shop/checkout',
                    'message' => 'Checkout session not found. Please try again.'
                ];
            }

            // Verify payment with Xendit API
            $xendit = Coderstm::xendit();
            $invoice = $xendit->getInvoice($paymentState['invoice_id']);

            // Check if payment is actually successful
            if (!in_array($invoice['status'], ['PAID', 'SETTLED'])) {
                Log::warning('Xendit success callback: Invoice not paid', [
                    'invoice_id' => $paymentState['invoice_id'],
                    'status' => $invoice['status']
                ]);
                return [
                    'success' => false,
                    'redirect_url' => '/user/shop/checkout',
                    'message' => 'Payment not completed. Status: ' . $invoice['status']
                ];
            }

            // Validate invoice data matches our expectations
            if ($invoice['external_id'] !== $paymentState['checkout_token']) {
                Log::error('Xendit success callback: External ID mismatch', [
                    'expected' => $paymentState['checkout_token'],
                    'received' => $invoice['external_id']
                ]);
                return [
                    'success' => false,
                    'redirect_url' => '/user/shop/checkout',
                    'message' => 'Payment validation failed. Please contact support.'
                ];
            }

            DB::beginTransaction();

            try {
                // Create order from checkout
                $order = $this->createOrderFromCheckout($checkout);

                // Create payment data
                $paymentData = XenditPayment::fromResponse(
                    [
                        'id' => $invoice['id'],
                        'status' => $invoice['status'],
                        'external_id' => $invoice['external_id'],
                        'transaction_id' => $invoice['id'],
                    ],
                    $this->paymentMethod->id,
                    $checkout->grand_total,
                    $checkout->currency ?? config('app.currency')
                );

                // Mark order as paid
                $order->markAsPaid($paymentData);

                // Update checkout status instead of deleting to preserve audit trail
                $checkout->update(['status' => 'completed']);

                // Clear payment state from cache
                Cache::forget('xendit_payment_states.' . $stateId);

                DB::commit();

                return [
                    'success' => true,
                    'redirect_url' => '/user/shop/cart',
                    'message' => 'Payment completed successfully!'
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error creating order for Xendit payment', [
                    'invoice_id' => $paymentState['invoice_id'],
                    'checkout_token' => $paymentState['checkout_token'],
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Xendit success callback error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'redirect_url' => '/user/shop/checkout',
                'message' => 'Payment processing error. Please contact support if your payment was deducted.'
            ];
        }
    }

    public function handleCancelCallback(Request $request): array
    {
        try {
            // Get state ID from query parameter
            $stateId = $request->query('state');

            if ($stateId) {
                // Retrieve payment state from cache
                $paymentState = Cache::get('xendit_payment_states.' . $stateId);

                if ($paymentState) {
                    // Clear payment state from cache
                    Cache::forget('xendit_payment_states.' . $stateId);
                }
            }
        } catch (\Exception $e) {
            Log::error('Xendit cancel callback error', [
                'error' => $e->getMessage()
            ]);
        }

        return [
            'success' => true,
            'redirect_url' => '/user/shop/checkout',
            'message' => 'Payment was cancelled. You can try again or choose a different payment method.'
        ];
    }

    public function setupPaymentIntent(Request $request, Checkout $checkout): array
    {
        try {
            $xendit = Coderstm::xendit();

            // Xendit only supports IDR in your account configuration
            // Force IDR currency and convert amount if needed
            $originalCurrency = strtoupper($checkout->currency ?? 'USD');
            $xenditCurrency = 'IDR';
            $amount = $checkout->grand_total;

            // If original currency is not IDR, convert to IDR
            if ($originalCurrency !== 'IDR') {
                // Simple conversion rate - in production, you should use a proper currency converter
                $conversionRates = [
                    'USD' => 15000, // 1 USD = 15,000 IDR (approximate)
                    'EUR' => 16500, // 1 EUR = 16,500 IDR (approximate)
                ];

                if (isset($conversionRates[$originalCurrency])) {
                    $amount = $checkout->grand_total * $conversionRates[$originalCurrency];
                } else {
                    throw new \Exception("Currency {$originalCurrency} is not supported by Xendit. Only IDR is supported in your account configuration.");
                }
            }

            // Generate unique state ID for this payment session
            $stateId = 'xendit_' . $checkout->token . '_' . time() . '_' . uniqid();

            // Create invoice using Xendit's Invoice API (simpler and more reliable)
            $invoiceData = [
                'external_id' => $checkout->token,
                'amount' => round($amount), // Ensure amount is integer for IDR
                'description' => "Order payment for {$checkout->email}" . ($originalCurrency !== 'IDR' ? " (converted from {$originalCurrency})" : ""),
                'invoice_duration' => 86400, // 24 hours
                'customer' => [
                    'given_names' => $checkout->first_name,
                    'surname' => $checkout->last_name,
                    'email' => $checkout->email,
                    'mobile_number' => $checkout->phone_number,
                ],
                'customer_notification_preference' => [
                    'invoice_created' => ['email'],
                    'invoice_reminder' => ['email'],
                    'invoice_paid' => ['email'],
                ],
                'success_redirect_url' => route('shop.checkout.success', ['provider' => 'xendit']) . '?state=' . $stateId,
                'failure_redirect_url' => route('shop.checkout.cancel', ['provider' => 'xendit']) . '?state=' . $stateId,
                'currency' => $xenditCurrency,
            ];

            $invoice = $xendit->createInvoice($invoiceData);

            // Store payment state in cache (expires in 24 hours)
            Cache::put('xendit_payment_states.' . $stateId, [
                'checkout_token' => $checkout->token,
                'invoice_id' => $invoice['id'],
                'external_id' => $invoice['external_id'],
                'amount' => $checkout->grand_total,
                'currency' => $checkout->currency,
                'created_at' => now()->toISOString(),
            ], 86400); // 24 hours in seconds

            return [
                'success' => true,
                'payment_url' => $invoice['invoice_url'],
                'invoice_id' => $invoice['id'],
                'external_id' => $invoice['external_id'],
                'original_currency' => $originalCurrency,
                'xendit_currency' => $xenditCurrency,
                'converted_amount' => round($amount),
                'state_id' => $stateId,
            ];
        } catch (\Exception $e) {
            Log::error('Xendit setupPaymentIntent error', [
                'checkout_token' => $checkout->token,
                'error' => $e->getMessage()
            ]);

            // Provide specific error message for currency issues
            if (strpos($e->getMessage(), 'currency') !== false && strpos($e->getMessage(), 'not configured') !== false) {
                throw new \Exception('Currency not supported. Xendit only supports IDR currency in your account configuration. Please contact Xendit to enable additional currencies or use a different payment method.');
            }

            throw new \Exception('Failed to create Xendit payment: ' . $e->getMessage());
        }
    }

    public function confirmPayment(Request $request, Checkout $checkout): array
    {
        $request->validate([
            'invoice_id' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $xendit = Coderstm::xendit();

            // Get invoice details from Xendit
            $invoice = $xendit->getInvoice($request->invoice_id);

            // Check if payment is successful
            $status = $invoice['status'];
            if (!in_array($status, ['PAID', 'SETTLED'])) {
                throw new \Exception("Payment not completed. Status: {$status}");
            }

            $order = $this->createOrderFromCheckout($checkout);

            $paymentData = XenditPayment::fromResponse(
                [
                    'id' => $invoice['id'],
                    'status' => $invoice['status'],
                    'external_id' => $invoice['external_id'],
                ],
                $this->paymentMethod->id,
                $checkout->grand_total,
                $checkout->currency ?? config('app.currency')
            );

            $order->markAsPaid($paymentData);

            DB::commit();

            return [
                'success' => true,
                'status' => 'success',
                'order_id' => $order->key,
                'invoice_id' => $invoice['id'],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Xendit confirmPayment error: ' . $e->getMessage(), [
                'checkout_token' => $checkout->token,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
