<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Coderstm\Payment\Mappers\PaystackPayment;
use Coderstm\Contracts\PaymentProcessorInterface;

class PaystackProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected PaymentMethod $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = PaymentMethod::where('provider', 'paystack')->where('active', true)->firstOrFail();
    }

    public function getProvider(): string
    {
        return 'paystack';
    }

    public function validateCallback(Request $request): ?string
    {
        // Paystack sends reference (which is our checkout token) in the callback
        $checkoutToken = $request->get('reference') ?? $request->get('checkout_token');

        // Additional validation can be added here (signature verification, etc.)
        if ($checkoutToken) {
            Log::info('Paystack callback validation successful', [
                'reference' => $request->get('reference'),
                'trxref' => $request->get('trxref'),
                'status' => $request->get('status'),
            ]);
        }

        return $checkoutToken;
    }

    public function setupPaymentIntent(Request $request, Checkout $checkout): array
    {
        $paystack = Coderstm::paystack();

        // Create transaction initialization
        $transactionData = [
            'email' => $checkout->email,
            'amount' => $checkout->grand_total * 100, // Convert to kobo (NGN smallest unit)
            'currency' => strtoupper($checkout->currency ?? 'NGN'),
            'reference' => 'ORDER_' . $checkout->token . '_' . time(),
            'callback_url' => route('shop.checkout.success', ['provider' => 'paystack']),
            'cancel_url' => route('shop.checkout.cancel', ['provider' => 'paystack']),
            'metadata' => [
                'checkout_token' => $checkout->token,
                'customer_name' => $checkout->first_name . ' ' . $checkout->last_name,
                'customer_phone' => $checkout->phone_number,
            ],
            'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
            'split_code' => null, // Can be used for marketplace applications
        ];

        try {
            // Initialize transaction using Paystack client
            $response = $paystack->transaction->initialize($transactionData);

            if (!$response->status) {
                throw new \Exception('Failed to initialize Paystack transaction: ' . $response->message);
            }

            return [
                'authorization_url' => $response->data->authorization_url,
                'access_code' => $response->data->access_code,
                'reference' => $response->data->reference,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to create Paystack transaction: ' . $e->getMessage());
        }
    }

    public function confirmPayment(Request $request, Checkout $checkout): array
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $paystack = Coderstm::paystack();

            // Verify transaction with Paystack
            $response = $paystack->transaction->verify([
                'reference' => $request->reference,
            ]);

            if (!$response->status) {
                throw new \Exception('Failed to verify Paystack transaction: ' . $response->message);
            }

            $transaction = $response->data;

            // Check if payment is successful
            if ($transaction->status !== 'success') {
                throw new \Exception("Payment not successful. Status: {$transaction->status}");
            }

            $order = $this->createOrderFromCheckout($checkout);

            $paymentData = PaystackPayment::fromResponse(
                [
                    'reference' => $transaction->reference,
                    'status' => $transaction->status,
                    'gateway_response' => $transaction->gateway_response ?? null,
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
                'reference' => $transaction->reference,
                'amount' => $transaction->amount / 100, // Convert back from kobo
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
