<?php

namespace Coderstm\Payment\Processors;

use Coderstm\Coderstm;
use Illuminate\Http\Request;
use Coderstm\Models\Shop\Checkout;
use Coderstm\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Coderstm\Payment\Mappers\PayPalPayment;
use Coderstm\Contracts\PaymentProcessorInterface;

class PaypalProcessor extends AbstractPaymentProcessor implements PaymentProcessorInterface
{
    protected PaymentMethod $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = PaymentMethod::where('provider', 'paypal')->where('active', true)->firstOrFail();
    }

    public function getProvider(): string
    {
        return 'paypal';
    }

    public function setupPaymentIntent(Request $request, Checkout $checkout): array
    {
        $paypal = Coderstm::paypal();

        $order = $paypal->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => strtoupper($request->currency ?? config('app.currency', 'USD')),
                        'value' => number_format($request->amount, 2, '.', ''),
                    ],
                    'description' => "Order payment for {$checkout->email}",
                    'reference_id' => $checkout->token,
                ]
            ],
            'application_context' => [
                'return_url' => route('shop.checkout.success', ['provider' => 'paypal']),
                'cancel_url' => route('shop.checkout.cancel', ['provider' => 'paypal']),
            ],
        ]);

        if ($order['status'] !== 'CREATED') {
            throw new \Exception('Failed to create PayPal order: ' . ($order['message'] ?? 'Unknown error'));
        }

        return [
            'paypal_order_id' => $order['id'],
            'status' => $order['status'],
        ];
    }

    public function confirmPayment(Request $request, Checkout $checkout): array
    {
        $request->validate([
            'paypal_order_id' => 'required|string',
            'payer_id' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $paypal = Coderstm::paypal();
            $capture = $paypal->capturePaymentOrder($request->paypal_order_id);

            if ($capture['status'] !== 'COMPLETED') {
                throw new \Exception('PayPal payment capture failed: ' . ($capture['message'] ?? 'Unknown error'));
            }

            $order = $this->createOrderFromCheckout($checkout);

            $paymentData = PayPalPayment::fromCapture(
                $capture,
                $this->paymentMethod->id,
                $checkout->grand_total,
                $checkout->currency ?? config('app.currency')
            );

            $order->markAsPaid($paymentData);

            DB::commit();

            return [
                'success' => true,
                'order_id' => $order->key,
                'transaction_id' => $paymentData->getTransactionId(),
                'status' => 'success',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
